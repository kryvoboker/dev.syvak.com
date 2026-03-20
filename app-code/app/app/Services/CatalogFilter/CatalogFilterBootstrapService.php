<?php

declare(strict_types=1);

namespace App\Services\CatalogFilter;

use App\Models\CatalogFilter\CatalogFilterIndexMeta;
use App\Models\CatalogFilter\CatalogFilterSet;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogFilterBootstrapService
{
    public function bootstrapDefaultCategorySet(): CatalogFilterSet
    {
        try {
            $defaults = (array) config('catalog-filter.defaults', []);

            $filter_set = CatalogFilterSet::query()->firstOrCreate(
                [
                    'code' => (string) ($defaults['set_code'] ?? 'default_category'),
                ],
                [
                    'context_type'                   => (string) ($defaults['context'] ?? 'category'),
                    'is_enabled'                     => (bool) ($defaults['is_enabled'] ?? true),
                    'is_price_filter_enabled'        => (bool) ($defaults['is_price_filter_enabled'] ?? true),
                    'is_attribute_filtering_enabled' => (bool) ($defaults['is_attribute_filtering_enabled'] ?? true),
                    'price_source_mode'              => (string) ($defaults['price_source_mode'] ?? 'both'),
                    'facet_strategy'                 => (string) ($defaults['facet_strategy'] ?? 'self_excluding'),
                    'discount_only_policy'           => (string) ($defaults['discount_only_policy'] ?? 'exclude_without_discount'),
                    'min_stock_quantity'             => (int) ($defaults['min_stock_quantity'] ?? 1),
                    'settings'                       => [],
                ],
            );

            CatalogFilterIndexMeta::query()->firstOrCreate(
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                ],
                [
                    'index_version'        => 1,
                    'active_index_version' => 1,
                    'last_status'          => 'ok',
                    'items_total'          => 0,
                    'values_total'         => 0,
                    'index_rows_total'     => 0,
                ],
            );

            Log::channel('daily')->info(
                'Catalog filter set bootstrapped.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'code'                  => (string) $filter_set->code,
                    'context_type'          => (string) $filter_set->getRawOriginal('context_type'),
                ],
            );

            return $filter_set->fresh(['indexMeta', 'groups.translations', 'groups.values.translations']) ?? $filter_set;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Catalog filter bootstrap failed.',
                [
                    'exception' => $throwable,
                ],
            );

            throw $throwable;
        }
    }
}
