<?php

declare(strict_types=1);

return [
    'name'        => 'ProductsCarousel',
    'description' => 'Localized storefront products carousel module.',
    'runtime'     => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
    ],
    'search' => [
        'result_limit' => 30,
    ],
    'settings' => [
        'default_source_mode'  => 'category_based',
        'allowed_source_modes' => [
            'category_based',
            'manual_only',
        ],
    ],
];
