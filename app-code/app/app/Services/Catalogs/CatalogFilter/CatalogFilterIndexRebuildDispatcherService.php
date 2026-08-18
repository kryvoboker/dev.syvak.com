<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Jobs\RebuildCatalogFilterIndexJob;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogFilterIndexRebuildDispatcherService
{
    public function __construct(private readonly CatalogFilterIndexRebuildService $rebuild_service)
    {
    }

    /**
     * @throws Throwable
     * @return array{status: string, rows_total: int, index_version: int}
     */
    public function dispatch(CatalogFilterSet $filter_set): array
    {
        if (! (bool) config('catalog-filter.rebuild.queue_enabled', false)) {
            /** @var array{status: string, rows_total: int, index_version: int} $summary */
            $summary = $this->rebuild_service->rebuild($filter_set);

            return $summary;
        }

        $index_meta = $filter_set->indexMeta()->firstOrCreate(
            ['catalog_filter_set_id' => (int) $filter_set->id],
            [
                'index_version' => 1,
                'active_index_version' => 1,
                'last_status' => 'stale',
                'items_total' => 0,
                'values_total' => 0,
                'index_rows_total' => 0,
            ],
        );

        if (! $index_meta->markAsQueued()) {
            return [
                'status' => is_scalar($index_meta->getRawOriginal('last_status')) ? (string) $index_meta->getRawOriginal('last_status') : 'unknown',
                'rows_total' => (int) $index_meta->index_rows_total,
                'index_version' => (int) $index_meta->active_index_version,
            ];
        }

        try {
            RebuildCatalogFilterIndexJob::dispatch((int) $filter_set->id);
        } catch (Throwable $throwable) {
            $index_meta->markAsStale();

            Log::channel('stack')->error(
                'Catalog filter index rebuild dispatch failed.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'exception' => $throwable,
                ],
            );

            throw $throwable;
        }

        return [
            'status' => 'queued',
            'rows_total' => (int) $index_meta->index_rows_total,
            'index_version' => (int) $index_meta->active_index_version,
        ];
    }
}
