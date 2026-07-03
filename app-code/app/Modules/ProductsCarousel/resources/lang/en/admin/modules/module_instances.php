<?php

declare(strict_types=1);

return [
    'products_carousel' => [
        'sections' => [
            'shared' => 'Module visibility and user content',
            'sorting' => 'Sorting and output limit settings',
            'source_mode' => 'Product source mode',
            'category_based_window' => 'Show products from categories',
            'category_products' => 'Selected products from selected categories',
            'manual_only_window' => 'Only selected products',
        ],
        'labels' => [
            'module_name' => 'Module name',
            'module_name_for_user' => 'Module name for user',
            'short_description_for_user' => 'Short description for user',
            'page_types' => 'Show on pages',
            'min_quantity' => 'Minimum stock quantity to show products',
            'products_limit' => 'How many products to show',
            'product_image_width' => 'Product image width',
            'product_image_height' => 'Product image height',
            'sort_mode' => 'Products sorting mode',
            'custom_sort_options' => 'Custom sorting combination',
            'custom_sort_price' => 'Sort by price',
            'custom_sort_name' => 'Sort by name',
            'custom_sort_date_added' => 'Sort by date added',
            'custom_sort_quantity' => 'Sort by stock quantity',
            'source_mode' => 'Products source mode',
            'categories_tree' => 'Categories tree',
            'use_selected_products_only' => 'Show only specific products',
            'search_products_in_selected_categories' => 'Live search in selected categories',
            'search_all_active_products' => 'Live search in all active products',
            'selected_products' => 'Selected products',
        ],
        'options' => [
            'source_mode' => [
                'category_based' => 'Show products from categories',
                'manual_only' => 'Only selected products',
            ],
            'sort_mode' => [
                'custom' => 'Custom products sorting',
                'random' => 'Random products sorting',
            ],
            'sort_direction' => [
                'none' => 'Do not use',
                'asc' => 'Ascending',
                'desc' => 'Descending',
            ],
            'custom_sort_options' => [
                'price_asc' => 'Price: low to high',
                'price_desc' => 'Price: high to low',
                'name_asc' => 'Name: A to Z',
                'name_desc' => 'Name: Z to A',
                'date_added_asc' => 'Date added: oldest first',
                'date_added_desc' => 'Date added: newest first',
                'quantity_asc' => 'Stock: low to high',
                'quantity_desc' => 'Stock: high to low',
            ],
        ],
        'actions' => [
            'select_all_categories' => 'Select all categories',
            'clear_all_categories' => 'Clear all categories',
        ],
        'helpers' => [
            'shared' => 'Control where the module is shown and define optional user-facing title and description text.',
            'module_name_for_user' => 'Optional. If empty, storefront can use default section title.',
            'short_description_for_user' => 'Optional. Short section text shown near the title in storefront.',
            'page_types' => 'Required. Select at least one page type where this module should be rendered.',
            'sorting' => 'Each field uses radio options, so you can choose only one direction per field and cannot select both asc and desc at the same time.',
            'min_quantity' => 'Only products with quantity greater than or equal to this value will be shown.',
            'products_limit' => 'Final number of products in module output after filtering and sorting.',
            'product_image_width' => 'Required width used for storefront product image conversion.',
            'product_image_height' => 'Required height used for storefront product image conversion.',
            'custom_sort_options' => 'Choose a direction for each required field. Keep "Do not use" for fields that should not participate in sorting.',
            'category_based_window' => 'Choose active categories in the tree. Child categories are shown under their parents.',
            'manual_only_window' => 'Select any active products from the full active catalog.',
            'search_products_in_selected_categories' => 'Type part of a product name, model, or SKU. Search scope is limited to selected categories.',
            'search_all_active_products' => 'Type part of a product name, model, or SKU. Search scope includes all active products.',
            'selected_products' => 'Use checkboxes to keep or remove products from the final module output.',
        ],
    ],
];
