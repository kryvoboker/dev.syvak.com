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

            [$created_count, $updated_count] = $this->syncSystemGroups($filter_set, $created_count, $updated_count);
            [$created_count, $updated_count] = $this->syncAttributeGroups($filter_set, $created_count, $updated_count);

            $total_groups = CatalogFilterGroup::query()
                ->where('catalog_filter_set_id', (int) $filter_set->id)
                ->count();

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
                'code'        => CatalogFilterGroupSourceTypeEnum::Price->value,
                'source_type' => CatalogFilterGroupSourceTypeEnum::Price->value,
                'source_id'   => null,
                'sort_order'  => 10,
                'get_key'     => CatalogFilterGroupSourceTypeEnum::Price->value,
            ],
        ];

        foreach ($system_groups as $payload) {
            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code'                  => (string) $payload['code'],
            ]);

            $was_existing_group = $group->exists;

            $group->source_type = (string) $payload['source_type'];
            $group->source_id   = null;

            if (! $was_existing_group) {
                $group->is_enabled = true;
                $group->sort_order = (int) $payload['sort_order'];
                $group->get_key    = (string) $payload['get_key'];
                $group->setAttribute('config', []);
            }

            if (blank((string) $group->get_key)) {
                $group->get_key = (string) $payload['get_key'];
            }

            if (! is_array($group->config)) {
                $group->setAttribute('config', []);
            }

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
        /** @var Collection<Attribute> $active_attributes */
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
            $group = CatalogFilterGroup::query()->firstOrNew([
                'catalog_filter_set_id' => (int) $filter_set->id,
                'code'                  => CatalogFilterGroupSourceTypeEnum::Attribute->value . '_' . (int) $attribute->id,
            ]);

            $was_existing_group = $group->exists;

            $group->source_type = CatalogFilterGroupSourceTypeEnum::Attribute->value;
            $group->source_id   = (int) $attribute->id;

            if (! $was_existing_group) {
                $group->is_enabled = true;
                $group->sort_order = $sort_order;
                $group->get_key    = 'filters[' . (int) $attribute->id . ']';
                $group->setAttribute('config', []);
            }

            if (blank((string) $group->get_key)) {
                $group->get_key = 'filters[' . (int) $attribute->id . ']';
            }

            if (! is_array($group->config)) {
                $group->setAttribute('config', []);
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

        return [$created_count, $updated_count];
    }

    private function syncSystemGroupTranslations(CatalogFilterGroup $group): void
    {
        foreach (new Language()->getActiveLanguages() as $language) {
            $language_code = (string) $language->code;
            $translate     = __('admin/catalogs/catalog-filter/catalog-filter-set.labels.price', locale: $language_code);

            CatalogFilterGroupTranslation::query()->updateOrCreate(
                [
                    'catalog_filter_group_id' => (int) $group->id,
                    'language_id'             => (int) $language->id,
                ],
                [
                    'label' => (string) ($translate ?? ucfirst($group->code)),
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
