<?php

declare(strict_types=1);

namespace App\Models\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterIndexRunModeEnum;
use App\Enums\CatalogFilter\CatalogFilterIndexStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogFilterIndexMeta extends Model
{
    protected $table = 'catalog_filter_index_meta';

    protected $fillable = [
        'catalog_filter_set_id',
        'index_version',
        'active_index_version',
        'building_index_version',
        'rebuild_lock_key',
        'rebuild_lock_acquired_at',
        'last_full_rebuild_at',
        'last_incremental_sync_at',
        'last_status',
        'last_error',
        'last_progress_percent',
        'last_run_mode',
        'items_total',
        'values_total',
        'index_rows_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'catalog_filter_set_id'    => 'integer',
            'index_version'            => 'integer',
            'active_index_version'     => 'integer',
            'building_index_version'   => 'integer',
            'rebuild_lock_acquired_at' => 'datetime',
            'last_full_rebuild_at'     => 'datetime',
            'last_incremental_sync_at' => 'datetime',
            'last_status'              => CatalogFilterIndexStatusEnum::class,
            'last_progress_percent'    => 'integer',
            'last_run_mode'            => CatalogFilterIndexRunModeEnum::class,
            'items_total'              => 'integer',
            'values_total'             => 'integer',
            'index_rows_total'         => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogFilterSet, $this>
     */
    public function filterSet(): BelongsTo
    {
        return $this->belongsTo(CatalogFilterSet::class, 'catalog_filter_set_id');
    }
}
