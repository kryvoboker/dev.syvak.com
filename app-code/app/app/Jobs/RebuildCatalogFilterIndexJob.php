<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\CatalogFilterIndexRebuildService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RebuildCatalogFilterIndexJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor = 900;

    public function __construct(public readonly int $filter_set_id)
    {
    }

    public function uniqueId(): string
    {
        return 'catalog-filter-index:' . $this->filter_set_id;
    }

    /**
     * @throws Throwable
     */
    public function handle(CatalogFilterIndexRebuildService $rebuild_service): void
    {
        $filter_set = CatalogFilterSet::query()->findOrFail($this->filter_set_id);

        Log::channel('daily')->info(
            'Queued catalog filter index rebuild started.',
            ['catalog_filter_set_id' => $this->filter_set_id],
        );

        $summary = $rebuild_service->rebuild($filter_set);

        Log::channel('daily')->info(
            'Queued catalog filter index rebuild finished.',
            [
                'catalog_filter_set_id' => $this->filter_set_id,
                'status' => (string) ($summary['status'] ?? 'unknown'),
                'index_version' => (int) ($summary['index_version'] ?? 0),
                'rows_total' => (int) ($summary['rows_total'] ?? 0),
            ],
        );
    }

    public function failed(Throwable $throwable): void
    {
        Log::channel('stack')->error(
            'Queued catalog filter index rebuild failed.',
            [
                'catalog_filter_set_id' => $this->filter_set_id,
                'exception' => $throwable,
            ],
        );
    }
}
