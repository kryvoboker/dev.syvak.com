<?php

declare(strict_types=1);

return [
    'contexts' => [
        'category' => 'Category',
        'catalog'  => 'Catalog',
        'search'   => 'Search',
    ],
    'price_source_modes' => [
        'base_only'     => 'Base only',
        'discount_only' => 'Discount only',
        'both'          => 'Both',
    ],
    'discount_only_policies' => [
        'exclude_without_discount' => 'Exclude products without active discount',
        'fallback_to_base'         => 'Fallback to base price when discount is missing',
    ],
    'facet_strategies' => [
        'all_results'    => 'All results',
        'self_excluding' => 'Self excluding',
    ],
    'group_source_types' => [
        'attribute' => 'Attribute',
        'price'     => 'Price',
        'stock'     => 'Stock',
        'system'    => 'System',
    ],
    'value_types' => [
        'string'  => 'String',
        'int'     => 'Integer',
        'decimal' => 'Decimal',
        'boolean' => 'Boolean',
        'range'   => 'Range',
    ],
    'index_statuses' => [
        'ok'      => 'Ok',
        'warning' => 'Warning',
        'failed'  => 'Failed',
        'running' => 'Running',
    ],
    'index_run_modes' => [
        'full'        => 'Full',
        'incremental' => 'Incremental',
        'dry_run'     => 'Dry run',
    ],
    'defaults' => [
        'context'                         => 'category',
        'contexts'                        => ['category'],
        'set_code'                        => 'default_category',
        'is_enabled'                      => true,
        'is_price_filter_enabled'         => true,
        'is_attribute_filtering_enabled'  => true,
        'price_source_mode'               => 'both',
        'discount_only_policy'            => 'exclude_without_discount',
        'facet_strategy'                  => 'self_excluding',
        'min_stock_quantity'              => 1,
        'rebuild_chunk_size'              => 1000,
        'rebuild_lock_timeout_seconds'    => 600,
        'max_selected_values_per_group'   => 30,
        'base_currency_indexing_required' => true,
    ],
    'canonical_url' => [
        'enabled'   => true,
        'sort_keys' => [
            'sort',
            'stock',
            'price_from',
            'price_to',
        ],
        'whitelist_keys' => [
            'sort',
            'stock',
            'price_from',
            'price_to',
            'filters',
            'page',
            'per_page',
        ],
    ],
];
