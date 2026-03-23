<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Catalog Filter',

    'tabs' => [
        'core'           => 'Core Settings',
        'filter_options' => 'Filter Options',
    ],

    'labels' => [
        'model'                           => 'Catalog Filter',
        'plural_model'                    => 'Catalog Filter',
        'code'                            => 'Code',
        'context_type'                    => 'On which page to apply the filter',
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
        'filter_items'                    => 'Filter Options',
        'source_type'                     => 'Source Type',
        'source_id'                       => 'Source ID',
        'sort_order'                      => 'Sort Order',
        'get_key'                         => 'GET Key',
        'get_value'                       => 'GET Value',
        'get_extra'                       => 'GET Extra',
        'key'                             => 'Key',
        'value'                           => 'Value',
        'filter_mode'                     => 'Filter Mode',
        'filter_mode_hint'                => 'Allowed values come from config/catalog-filter.php',
        'filter_mode_description'         => 'Filter Mode Description',
        'min_price'                       => 'Minimum Price',
        'max_price'                       => 'Maximum Price',
        'step'                            => 'Price Step',
        'option_labels'                   => 'Option Labels',
        'option_label_value'              => 'Label',
    ],

    'helpers' => [
        'see_wiki' => 'See "Wiki" page.',
    ],

    'sections' => [
        'general'           => 'General',
        'strategy_by_price' => 'Price Filtering Strategy',
        'strategy_by_stock' => 'Stock Filtering Strategy',
        'rebuild'           => 'Rebuild Settings',
        'index_meta'        => 'Index Runtime Status',
        'filter_options'    => 'Point-by-point filter options setup',
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

    'filter_mode_options' => [
        'range'    => 'Range',
        'boolean'  => 'Boolean',
        'multiple' => 'Multiple',
    ],

    'filter_mode_descriptions' => [
        'range'    => 'Use this mode for numeric values and ranges (for example, price intervals).',
        'boolean'  => 'Use this mode for yes/no flags (for example, in stock).',
        'multiple' => 'Use this mode when users can select one or more values from a list.',
    ],
];
