<?php

declare(strict_types=1);

return [
    'name' => 'ProductsCarousel',
    'description' => 'Localized storefront products carousel module.',
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
        'storefront' => [
            'data_service' => 'Services\\ProductsCarouselModuleDataService',
            'view' => 'productscarousel::storefront.products-carousel',
            'view_data_key' => 'products_carousel_module_data',
        ],
    ],
    'search' => [
        'result_limit' => 30,
    ],
    'settings' => [
        'default_source_mode' => 'category_based',
        'default_page_types' => [
            'home',
        ],
        'default_min_quantity' => 1,
        'default_products_limit' => 15,
        'default_image_width' => 420,
        'default_image_height' => 420,
        'default_sort_mode' => 'custom',
        'allowed_source_modes' => [
            'category_based',
            'manual_only',
        ],
        'allowed_sort_modes' => [
            'custom',
            'random',
        ],
        'allowed_sort_options' => [
            'price_asc',
            'price_desc',
            'name_asc',
            'name_desc',
            'date_added_asc',
            'date_added_desc',
            'quantity_asc',
            'quantity_desc',
        ],
    ],
];
