<?php

declare(strict_types=1);

namespace App\Services\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterValueTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValueTranslation;
use App\Models\Catalogs\Products\ProductToAttribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FilterValueGeneratorService
{
    /**
     * @return array<string, int>
     */
    public function sync(CatalogFilterSet $filter_set): array
    {
        try {
            $created_count = 0;
            $updated_count = 0;
            $removed_count = 0;

            $groups = CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($groups as $group) {
                $summary = match ($group->source_type) {
                    CatalogFilterGroupSourceTypeEnum::Stock     => $this->syncStockValues($group),
                    CatalogFilterGroupSourceTypeEnum::Attribute => $this->syncAttributeValues($group),
                    default                                     => [
                        'created_count' => 0,
                        'updated_count' => 0,
                        'removed_count' => 0,
                    ],
                };

                $created_count += (int) $summary['created_count'];
                $updated_count += (int) $summary['updated_count'];
                $removed_count += (int) $summary['removed_count'];
            }

            $total_values = (int) CatalogFilterValue::query()
                ->whereIn('catalog_filter_group_id', $groups->pluck('id')->all())
                ->count();

            Log::channel('daily')->info(
                'Catalog filter values synchronized.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'created_count'         => $created_count,
                    'updated_count'         => $updated_count,
                    'removed_count'         => $removed_count,
                    'total_values'          => $total_values,
                ],
            );

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
    private function syncStockValues(CatalogFilterGroup $group): array
    {
        $value = CatalogFilterValue::query()->firstOrNew([
            'catalog_filter_group_id' => (int) $group->id,
            'code'                    => 'in_stock',
        ]);

        $was_existing_value = $value->exists;

        $value->fill([
            'value_type'   => CatalogFilterValueTypeEnum::Boolean->value,
            'value_string' => 'in_stock',
            'value_number' => null,
            'range_from'   => null,
            'range_to'     => null,
            'is_enabled'   => true,
            'sort_order'   => 10,
            'meta'         => [],
        ]);
        $value->save();

        foreach (new Language()->getActiveLanguages() as $language) {
            $label = (string) match ((string) $language->code) {
                'uk'    => 'В наявності',
                default => 'In stock',
            };

            CatalogFilterValueTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_value_id' => (int) $value->id,
                    'language_id'             => (int) $language->id,
                ],
                [
                    'label' => $label,
                ],
            );
        }

        return [
            'created_count' => $was_existing_value ? 0 : 1,
            'updated_count' => $was_existing_value ? 1 : 0,
            'removed_count' => 0,
        ];
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

        $active_values = ProductToAttribute::query()
            ->where('attribute_id', $attribute_id)
            ->whereNotNull('text')
            ->whereHas('product', function ($query): void {
                $query->where('is_active', true);
            })
            ->select('text')
            ->distinct()
            ->orderBy('text')
            ->pluck('text')
            ->filter(fn (mixed $value): bool => filled((string) $value))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->values();

        $active_codes  = [];
        $created_count = 0;
        $updated_count = 0;

        foreach ($active_values as $sort_index => $value_label) {
            $value_code     = $this->buildValueCode($value_label);
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

            $this->syncAttributeValueTranslations($value, $value_label, $attribute_id);

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
        int $attribute_id,
    ): void {
        $translations_by_language = ProductToAttribute::query()
            ->where('attribute_id', $attribute_id)
            ->where('text', $fallback_label)
            ->whereNotNull('language_id')
            ->select('language_id', 'text')
            ->distinct()
            ->get()
            ->keyBy(fn (ProductToAttribute $attribute_text): int => (int) $attribute_text->language_id);

        foreach (new Language()->getActiveLanguages() as $language) {
            $translated_label = (string) optional(
                $translations_by_language->get((int) $language->id),
            )->text;

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

    private function buildValueCode(string $value_label): string
    {
        $slug = Str::slug($value_label, '_');

        if (filled($slug)) {
            return $slug;
        }

        return 'value_' . sha1($value_label);
    }
}
