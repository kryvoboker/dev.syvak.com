<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterIndexStatusEnum;
use App\Models\Catalogs\CatalogFilter\CatalogFilterIndexMeta;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use Illuminate\Support\Facades\Log;

class CatalogFilterIndexFreshnessService
{
    public function markStale(CatalogFilterSet $filter_set): void
    {
        /** @var CatalogFilterIndexMeta $index_meta */
        $index_meta = $filter_set->indexMeta()->firstOrCreate(
            ['catalog_filter_set_id' => (int) $filter_set->id],
            [
                'index_version' => 1,
                'active_index_version' => 1,
                'last_status' => CatalogFilterIndexStatusEnum::Ok->value,
                'items_total' => 0,
                'values_total' => 0,
                'index_rows_total' => 0,
            ],
        );

        if (! $index_meta->markAsStale()) {
            return;
        }

        Log::channel('daily')->info(
            'Catalog filter index marked as stale.',
            [
                'catalog_filter_set_id' => (int) $filter_set->id,
                'active_index_version' => (int) $index_meta->active_index_version,
            ],
        );
    }
}
