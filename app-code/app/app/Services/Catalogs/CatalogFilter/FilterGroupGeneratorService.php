<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroupTranslation;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class FilterGroupGeneratorService
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
            [$created_count, $updated_count, $canonical_group_codes] = $this->syncSystemGroups(
                $filter_set,
                $created_count,
                $updated_count,
            );
            [$created_count, $updated_count, $canonical_group_codes] = $this->syncAttributeGroups(
                $filter_set,
                $created_count,
                $updated_count,
                $canonical_group_codes,
            );

            $disabled_count = $this->disableObsoleteAttributeGroups(
                $filter_set,
                $canonical_group_codes,
            );

            app(CatalogFilterIndexFreshnessService::class)->markStale($filter_set);

            $total_groups = CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->count();

            if ($disabled_count > 0) {
                Log::channel('daily')->info(
                    'Obsolete catalog filter groups were disabled.',
                    [
                        'catalog_filter_set_id' => (int) $filter_set->id,
                        'disabled_count' => $disabled_count,
                    ],
                );
            }

            return [
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'disabled_count' => $disabled_count,
                'total_groups' => $total_groups,
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Catalog filter groups synchronization failed.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'exception' => $throwable,
                ],
            );

            throw $throwable;
        }
    }

    /**
     * @return array{0: int, 1: int, 2: array<int, string>}
     */
    private function syncSystemGroups(CatalogFilterSet $filter_set, int $created_count, int $updated_count): array
    {
        $system_groups = [
            [
                'code' => CatalogFilterGroupSourceTypeEnum::Price->value,
                'source_type' => CatalogFilterGroupSourceTypeEnum::Price->value,
                'source_id' => null,
                'sort_order' => 10,
                'get_key' => CatalogFilterGroupSourceTypeEnum::Price->value,
            ],
        ];

        foreach ($system_groups as $payload) {
            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code' => (string) $payload['code'],
            ]);

            $was_existing_group = $group->exists;

            $group->source_type = CatalogFilterGroupSourceTypeEnum::from((string) $payload['source_type']);
            $group->source_id = null;

            if (! $was_existing_group) {
                $group->is_enabled = true;
                $group->sort_order = (int) $payload['sort_order'];
                $group->get_key = (string) $payload['get_key'];
                $group->setAttribute('config', []);
            }

            if (blank((string) $group->get_key)) {
                $group->get_key = (string) $payload['get_key'];
            }

            $group->save();

            $this->syncSystemGroupTranslations($group);

            if ($was_existing_group) {
                $updated_count++;
            } else {
                $created_count++;
            }
        }

        return [$created_count, $updated_count, array_column($system_groups, 'code')];
    }

    /**
     * @param  array<int, string>  $canonical_group_codes
     * @return array{0: int, 1: int, 2: array<int, string>}
     */
    private function syncAttributeGroups(
        CatalogFilterSet $filter_set,
        int $created_count,
        int $updated_count,
        array $canonical_group_codes,
    ): array {
        /** @var Collection<int, Attribute> $active_attributes */
        $active_attributes = Attribute::query()
            ->where('is_active', true)
            ->whereHas('productToAttribute.variant.product', function ($query): void {
                $query->where('products.is_active', true);
            })
            ->with('attributeDescription')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sort_order = 100;

        foreach ($active_attributes as $attribute) {
            $group_code = CatalogFilterGroupSourceTypeEnum::Attribute->value . '_' . (int) $attribute->id;
            $canonical_group_codes[] = $group_code;

            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code' => $group_code,
            ]);

            $was_existing_group = $group->exists;

            $group->source_type = CatalogFilterGroupSourceTypeEnum::Attribute;
            $source_id = (int) $attribute->id;

            if ($source_id > 0) {
                $group->source_id = $source_id;
            }

            if (! $was_existing_group) {
                $group->is_enabled = true;
                $group->sort_order = $sort_order;
                $group->get_key = 'filters[' . (int) $attribute->id . ']';
                $group->setAttribute('config', []);
            }

            if (blank((string) $group->get_key)) {
                $group->get_key = 'filters[' . (int) $attribute->id . ']';
            }

            $group->save();

            $this->syncAttributeGroupTranslations($group, $attribute);

            if ($was_existing_group) {
                $updated_count++;
            } else {
                $created_count++;
            }

            $sort_order += 10;
        }

        return [$created_count, $updated_count, array_values(array_unique($canonical_group_codes))];
    }

    /**
     * @param  array<int, string>  $canonical_group_codes
     */
    private function disableObsoleteAttributeGroups(
        CatalogFilterSet $filter_set,
        array $canonical_group_codes,
    ): int {
        return CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->where('source_type', CatalogFilterGroupSourceTypeEnum::Attribute->value)
            ->where('is_enabled', true)
            ->whereNotIn('code', $canonical_group_codes)
            ->update(['is_enabled' => false]);
    }

    private function syncSystemGroupTranslations(CatalogFilterGroup $group): void
    {
        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_code = (string) $language->code;
            $translate = __('admin/catalogs/catalog-filter/catalog-filter-set.labels.price', locale: $language_code);

            CatalogFilterGroupTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_group_id' => (int) $group->id,
                    'language_id' => (int) $language->id,
                ],
                [
                    'label' => (string) $translate,
                ],
            );
        }
    }

    private function syncAttributeGroupTranslations(CatalogFilterGroup $group, Attribute $attribute): void
    {
        foreach ((new Language())->getActiveLanguages() as $language) {
            $attribute_description = optional(
                $attribute->attributeDescription
                    ->firstWhere('language_id', (int) $language->id),
            );
            $attribute_name_value = data_get($attribute_description, 'name');
            $attribute_name = is_scalar($attribute_name_value) ? (string) $attribute_name_value : '';

            CatalogFilterGroupTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_group_id' => (int) $group->id,
                    'language_id' => (int) $language->id,
                ],
                [
                    'label' => filled($attribute_name)
                        ? $attribute_name
                        : 'Attribute #' . (int) $attribute->id,
                ],
            );
        }
    }
}
