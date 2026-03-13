<?php

declare(strict_types=1);

return [
    'labels' => [
        'model'         => 'Module Settings',
        'plural_model'  => 'Module Settings',
        'name'          => 'Name',
        'placement'     => 'Placement',
        'context_key'   => 'Context Key',
        'is_enabled'    => 'Enabled',
        'sort_order'    => 'Sort Order',
        'settings'      => 'Settings',
        'meta'          => 'Meta',
        'setting_key'   => 'Key',
        'setting_value' => 'Value',
    ],
    'columns' => [
        'name'        => 'Name',
        'placement'   => 'Placement',
        'context_key' => 'Context Key',
        'is_enabled'  => 'Enabled',
        'sort_order'  => 'Sort Order',
    ],
    'filters' => [
        'is_enabled'    => 'State',
        'enabled_only'  => 'Enabled Only',
        'disabled_only' => 'Disabled Only',
    ],
    'actions' => [
        'add'       => 'Add',
        'duplicate' => 'Duplicate',
    ],
    'placeholders' => [
        'placement'   => 'home.hero',
        'context_key' => 'product.card',
    ],
    'notifications' => [
        'created'    => 'Module instance created.',
        'duplicated' => 'Module instance duplicated.',
    ],
    'pages' => [
        'create_title' => 'Create settings for module ":module"',
        'edit_title'   => 'Edit settings for module ":module"',
    ],
    'products_carousel' => [
        'sections' => [
            'source_mode'           => 'Product source mode',
            'category_based_window' => 'Show products from categories',
            'category_products'     => 'Selected products from selected categories',
            'manual_only_window'    => 'Only selected products',
        ],
        'labels' => [
            'module_name'                            => 'Module name',
            'source_mode'                            => 'Products source mode',
            'categories_tree'                        => 'Categories tree',
            'use_selected_products_only'             => 'Show only specific products',
            'search_products_in_selected_categories' => 'Live search in selected categories',
            'search_all_active_products'             => 'Live search in all active products',
            'selected_products'                      => 'Selected products',
        ],
        'options' => [
            'source_mode' => [
                'category_based' => 'Show products from categories',
                'manual_only'    => 'Only selected products',
            ],
        ],
        'actions' => [
            'select_all_categories' => 'Select all categories',
            'clear_all_categories'  => 'Clear all categories',
        ],
        'helpers' => [
            'category_based_window'                  => 'Choose active categories in the tree. Child categories are shown under their parents.',
            'manual_only_window'                     => 'Select any active products from the full active catalog.',
            'search_products_in_selected_categories' => 'Type part of a product name, model, or SKU. Search scope is limited to selected categories.',
            'search_all_active_products'             => 'Type part of a product name, model, or SKU. Search scope includes all active products.',
            'selected_products'                      => 'Use checkboxes to keep or remove products from the final module output.',
        ],
    ],
];
