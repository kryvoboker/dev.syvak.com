<?php

declare(strict_types=1);

namespace App\Services\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\CatalogFilter\CatalogFilterGroup;
use App\Models\CatalogFilter\CatalogFilterGroupTranslation;
use App\Models\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\Attributes\Attribute;
use Illuminate\Support\Facades\Log;
use Throwable;

class FilterGroupGeneratorService
{
    /**
     * @return array<string, int>
     */
    public function sync(CatalogFilterSet $filter_set): array
    {
        try {
            $created_count = 0;
            $updated_count = 0;

            [$created_count, $updated_count] = $this->syncSystemGroups($filter_set, $created_count, $updated_count);
            [$created_count, $updated_count] = $this->syncAttributeGroups($filter_set, $created_count, $updated_count);

            $total_groups = (int) CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->count();

            Log::channel('daily')->info(
                'Catalog filter groups synchronized.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'created_count'         => $created_count,
                    'updated_count'         => $updated_count,
                    'total_groups'          => $total_groups,
                ],
            );

            return [
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_groups'  => $total_groups,
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Catalog filter groups synchronization failed.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'exception'             => $throwable,
                ],
            );

            throw $throwable;
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function syncSystemGroups(CatalogFilterSet $filter_set, int $created_count, int $updated_count): array
    {
        $system_groups = [
            [
                'code'        => 'price',
                'source_type' => CatalogFilterGroupSourceTypeEnum::Price->value,
                'source_id'   => null,
                'sort_order'  => 10,
                'get_key'     => 'price',
            ],
            [
                'code'        => 'stock',
                'source_type' => CatalogFilterGroupSourceTypeEnum::Stock->value,
                'source_id'   => null,
                'sort_order'  => 20,
                'get_key'     => 'stock',
            ],
        ];

        foreach ($system_groups as $payload) {
            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code'                  => (string) $payload['code'],
            ]);

            $was_existing_group = $group->exists;

            $group->fill([
                'source_type' => (string) $payload['source_type'],
                'source_id'   => null,
                'is_enabled'  => true,
                'sort_order'  => (int) $payload['sort_order'],
                'get_key'     => (string) $payload['get_key'],
                'config'      => [],
            ]);
            $group->save();

            $this->syncSystemGroupTranslations($group);

            if ($was_existing_group) {
                $updated_count++;
            } else {
                $created_count++;
            }
        }

        return [$created_count, $updated_count];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function syncAttributeGroups(CatalogFilterSet $filter_set, int $created_count, int $updated_count): array
    {
        $active_attributes = Attribute::query()
            ->where('is_active', true)
            ->whereHas('productToAttribute.product', function ($query): void {
                $query->where('is_active', true);
            })
            ->with('attributeDescription')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sort_order = 100;

        foreach ($active_attributes as $attribute) {
            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code'                  => 'attribute_' . (int) $attribute->id,
            ]);

            $was_existing_group = $group->exists;

            $group->fill([
                'source_type' => CatalogFilterGroupSourceTypeEnum::Attribute->value,
                'source_id'   => (int) $attribute->id,
                'is_enabled'  => true,
                'sort_order'  => $sort_order,
                'get_key'     => 'filters[' . (int) $attribute->id . ']',
                'config'      => [],
            ]);
            $group->save();

            $this->syncAttributeGroupTranslations($group, $attribute);

            if ($was_existing_group) {
                $updated_count++;
            } else {
                $created_count++;
            }

            $sort_order += 10;
        }

        return [$created_count, $updated_count];
    }

    private function syncSystemGroupTranslations(CatalogFilterGroup $group): void
    {
        $label_by_code = [
            'price' => [
                'en' => 'Price',
                'uk' => 'Ціна',
            ],
            'stock' => [
                'en' => 'Stock',
                'uk' => 'Наявність',
            ],
        ];

        $labels = $label_by_code[$group->code] ?? [];

        foreach (new Language()->getActiveLanguages() as $language) {
            $language_code = (string) $language->code;

            CatalogFilterGroupTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_group_id' => (int) $group->id,
                    'language_id'             => (int) $language->id,
                ],
                [
                    'label' => (string) ($labels[$language_code] ?? ucfirst($group->code)),
                ],
            );
        }
    }

    private function syncAttributeGroupTranslations(CatalogFilterGroup $group, Attribute $attribute): void
    {
        foreach (new Language()->getActiveLanguages() as $language) {
            $attribute_name = (string) optional(
                $attribute->attributeDescription
                    ->firstWhere('language_id', (int) $language->id),
            )->name;

            CatalogFilterGroupTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_group_id' => (int) $group->id,
                    'language_id'             => (int) $language->id,
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
