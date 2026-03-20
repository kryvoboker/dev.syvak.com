<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Catalog Filter',

    'labels' => [
        'model'                           => 'Catalog Filter',
        'plural_model'                    => 'Catalog Filter',
        'code'                            => 'Code',
        'context_type'                    => 'Context Type',
        'is_enabled'                      => 'Enabled',
        'is_price_filter_enabled'         => 'Price Filter Enabled',
        'is_attribute_filtering_enabled'  => 'Attribute Filtering Enabled',
        'price_source_mode'               => 'Price Source Mode',
        'discount_only_policy'            => 'Discount Only Policy',
        'facet_strategy'                  => 'Facet Strategy',
        'min_stock_quantity'              => 'Minimum Stock Quantity',
        'rebuild_chunk_size'              => 'Rebuild Chunk Size',
        'rebuild_lock_timeout_seconds'    => 'Rebuild Lock Timeout (seconds)',
        'max_selected_values_per_group'   => 'Max Selected Values Per Group',
        'base_currency_indexing_required' => 'Base Currency Indexing Required',
        'active_index_version'            => 'Active Index Version',
        'building_index_version'          => 'Building Index Version',
        'last_status'                     => 'Last Status',
        'last_run_mode'                   => 'Last Run Mode',
        'last_progress_percent'           => 'Last Progress Percent',
        'index_rows_total'                => 'Index Rows Total',
        'last_full_rebuild_at'            => 'Last Full Rebuild At',
        'last_incremental_sync_at'        => 'Last Incremental Sync At',
        'rebuild_lock_key'                => 'Rebuild Lock Key',
        'rebuild_lock_acquired_at'        => 'Rebuild Lock Acquired At',
    ],

    'sections' => [
        'general'    => 'General',
        'strategy'   => 'Filtering Strategy',
        'rebuild'    => 'Rebuild Settings',
        'index_meta' => 'Index Runtime Status',
    ],

    'actions' => [
        'refresh_index_status' => 'Refresh Index Status',
        'sync_groups'          => 'Sync Groups',
        'sync_values'          => 'Sync Values',
        'sync_all'             => 'Sync Groups & Values',
    ],

    'notifications' => [
        'index_status_refreshed' => 'Index runtime status refreshed.',
        'groups_synced'          => 'Groups synchronized. Created: :created, Updated: :updated, Total: :total.',
        'values_synced'          => 'Values synchronized. Created: :created, Updated: :updated, Removed: :removed, Total: :total.',
        'all_synced'             => 'Catalog filter synchronized. Groups: :groups_total, Values: :values_total.',
    ],
];
