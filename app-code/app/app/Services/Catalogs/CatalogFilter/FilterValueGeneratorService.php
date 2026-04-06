<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterValueTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValueTranslation;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FilterValueGeneratorService
{
    /**
     * @throws Throwable
     *
     * @return array<string, int>
     */
    public function sync(CatalogFilterSet $filter_set): array
    {
        try {
            $created_count = 0;
            $updated_count = 0;
            $removed_count = 0;

            /** @var Collection<CatalogFilterGroup> $groups */
            $groups = CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($groups as $group) {
                $summary = match ($group->source_type) {
                    CatalogFilterGroupSourceTypeEnum::Attribute => $this->syncAttributeValues($group),
                    default                                     => [
                        'created_count' => 0,
                        'updated_count' => 0,
                        'removed_count' => 0,
                    ],
                };

                $created_count += $summary['created_count'];
                $updated_count += $summary['updated_count'];
                $removed_count += $summary['removed_count'];
            }

            $total_values = CatalogFilterValue::query()
                ->whereIn('catalog_filter_group_id', $groups->pluck('id')->all())
                ->count();

            return [
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'removed_count' => $removed_count,
                'total_values'  => $total_values,
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Catalog filter values synchronization failed.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'exception'             => $throwable,
                ],
            );

            throw $throwable;
        }
    }

    /**
     * @return array<string, int>
     */
    private function syncAttributeValues(CatalogFilterGroup $group): array
    {
        $attribute_id = (int) $group->source_id;

        if ($attribute_id <= 0) {
            return [
                'created_count' => 0,
                'updated_count' => 0,
                'removed_count' => 0,
            ];
        }

        $value_options = $this->resolveAttributeValueOptions($attribute_id);

        $active_codes  = [];
        $created_count = 0;
        $updated_count = 0;

        foreach ($value_options as $sort_index => $value_option) {
            $canonical_key = (string) Arr::get($value_option, 'canonical_key', '');
            $value_label   = (string) Arr::get($value_option, 'canonical_label', '');

            if (blank($canonical_key) || blank($value_label)) {
                continue;
            }

            $value_code     = $this->buildValueCode($canonical_key);
            $active_codes[] = $value_code;

            $value = CatalogFilterValue::query()->firstOrNew([
                'catalog_filter_group_id' => (int) $group->id,
                'code'                    => $value_code,
            ]);

            $was_existing_value = $value->exists;

            $value->fill([
                'value_type'   => CatalogFilterValueTypeEnum::String->value,
                'value_string' => $value_label,
                'value_number' => null,
                'range_from'   => null,
                'range_to'     => null,
                'is_enabled'   => true,
                'sort_order'   => ($sort_index + 1) * 10,
                'meta'         => [],
            ]);
            $value->save();

            $this->syncAttributeValueTranslations(
                value: $value,
                fallback_label: $value_label,
                labels_by_language: (array) Arr::get($value_option, 'labels_by_language', []),
            );

            if ($was_existing_value) {
                $updated_count++;
            } else {
                $created_count++;
            }
        }

        $removed_count = CatalogFilterValue::query()
            ->where('catalog_filter_group_id', (int) $group->id)
            ->whereNotIn('code', $active_codes)
            ->delete();

        return [
            'created_count' => $created_count,
            'updated_count' => $updated_count,
            'removed_count' => $removed_count,
        ];
    }

    private function syncAttributeValueTranslations(
        CatalogFilterValue $value,
        string $fallback_label,
        array $labels_by_language,
    ): void {
        foreach (new Language()->getActiveLanguages() as $language) {
            $translated_label = trim((string) ($labels_by_language[(int) $language->id] ?? ''));

            CatalogFilterValueTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_value_id' => (int) $value->id,
                    'language_id'             => (int) $language->id,
                ],
                [
                    'label' => filled($translated_label) ? $translated_label : $fallback_label,
                ],
            );
        }
    }

    /**
     * @return array<int, array{canonical_key: string, canonical_label: string, labels_by_language: array<int, string>}>
     */
    private function resolveAttributeValueOptions(int $attribute_id): array
    {
        /** @var array<int, ProductVariantAttributeValue> $attribute_rows */
        $attribute_rows = ProductVariantAttributeValue::query()
            ->where('attribute_id', $attribute_id)
            ->whereNotNull('value_string')
            ->whereHas('variant', function ($query): void {
                $query
                    ->where('is_active', true)
                    ->whereHas('product', function ($product_query): void {
                        $product_query->where('is_active', true);
                    });
            })
            ->select('product_variant_id', 'language_id', 'value_string')
            ->orderBy('product_variant_id')
            ->orderBy('language_id')
            ->get()
            ->all();

        if ($attribute_rows === []) {
            return [];
        }

        $preferred_language_ids = $this->resolvePreferredLanguageIds();
        $options_by_key         = [];

        foreach (collect($attribute_rows)->groupBy('product_variant_id') as $product_rows) {
            $labels_by_language = [];

            foreach ($product_rows as $product_row) {
                $language_id = (int) $product_row->language_id;
                $label       = trim((string) $product_row->value_string);

                if ($language_id <= 0 || blank($label)) {
                    continue;
                }

                $labels_by_language[$language_id] = $label;
            }

            if ($labels_by_language === []) {
                continue;
            }

            $canonical_label = $this->resolveCanonicalLabel($labels_by_language, $preferred_language_ids);
            $canonical_key   = $this->normalizeValueKey($canonical_label);

            if (blank($canonical_key)) {
                continue;
            }

            $option = $options_by_key[$canonical_key] ?? [
                'canonical_key'      => $canonical_key,
                'canonical_label'    => $canonical_label,
                'labels_by_language' => [],
                'labels_frequency'   => [],
            ];

            $option['canonical_label'] = $option['canonical_label'] ?: $canonical_label;

            foreach ($labels_by_language as $language_id => $label) {
                $normalized_label_key = $this->normalizeValueKey($label);

                if (blank($normalized_label_key)) {
                    continue;
                }

                $option['labels_frequency'][$language_id][$normalized_label_key]   = ($option['labels_frequency'][$language_id][$normalized_label_key] ?? 0) + 1;
                $option['labels_by_language'][$language_id][$normalized_label_key] = $label;
            }

            $options_by_key[$canonical_key] = $option;
        }

        /** @var array<int, array{canonical_key: string, canonical_label: string, labels_by_language: array<int, string>}> $normalized_options */
        $normalized_options = collect($options_by_key)
            ->map(function (array $option): array {
                $labels_by_language = [];

                foreach ($option['labels_frequency'] as $language_id => $variants_frequency) {
                    arsort($variants_frequency);
                    $selected_variant_key = (string) array_key_first($variants_frequency);
                    $variant_labels       = (array) ($option['labels_by_language'][$language_id] ?? []);

                    $selected_label = (string) ($variant_labels[$selected_variant_key] ?? '');

                    if (filled($selected_label)) {
                        $labels_by_language[(int) $language_id] = $selected_label;
                    }
                }

                $canonical_label = trim((string) $option['canonical_label']);

                return [
                    'canonical_key'   => (string) $option['canonical_key'],
                    'canonical_label' => filled($canonical_label)
                        ? $canonical_label
                        : (string) (collect($labels_by_language)->first() ?? ''),
                    'labels_by_language' => $labels_by_language,
                ];
            })
            ->filter(fn (array $option): bool => filled((string) $option['canonical_key']) && filled((string) $option['canonical_label']))
            ->sortBy(fn (array $option): string => mb_strtolower((string) $option['canonical_label']))
            ->values()
            ->all();

        return $normalized_options;
    }

    /**
     * @param  array<int, string>  $labels_by_language
     * @param  array<int, int>  $preferred_language_ids
     */
    private function resolveCanonicalLabel(array $labels_by_language, array $preferred_language_ids): string
    {
        foreach ($preferred_language_ids as $language_id) {
            $label = trim((string) ($labels_by_language[$language_id] ?? ''));

            if (filled($label)) {
                return $label;
            }
        }

        $fallback_label = collect($labels_by_language)
            ->map(fn (mixed $label): string => trim((string) $label))
            ->filter(fn (string $label): bool => filled($label))
            ->sort()
            ->first();

        return (string) ($fallback_label ?? '');
    }

    /**
     * @return array<int, int>
     */
    private function resolvePreferredLanguageIds(): array
    {
        return (new Language())
            ->getActiveLanguages()
            ->pluck('id')
            ->map(fn (mixed $language_id): int => (int) $language_id)
            ->values()
            ->all();
    }

    private function normalizeValueKey(string $value_label): string
    {
        $normalized_value = Str::of($value_label)
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->lower()
            ->toString();

        return $normalized_value;
    }

    private function buildValueCode(string $value_label): string
    {
        /**
         * Filter option codes are used in URL query parameters, so we keep
         * hyphen-separated slugs for SEO/readability and stable search engine parsing.
         */
        $slug = Str::slug($value_label, '-');

        if (filled($slug)) {
            return $slug;
        }

        return 'value_' . sha1($value_label);
    }
}
