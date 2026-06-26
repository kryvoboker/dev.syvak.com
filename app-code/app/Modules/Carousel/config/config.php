<?php

declare(strict_types=1);

return [
    'name' => 'Carousel',
    'description' => 'Localized storefront carousel module for hero and other placements.',
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
        'storefront' => [
            'data_service' => 'Services\\CarouselModuleDataService',
            'view' => 'carousel::storefront.main-carousel',
            'view_data_key' => 'carousel_module_data',
        ],
    ],
    'storefront' => [
        'desktop_image' => [
            'width' => 1220,
            'height' => 720,
            'max_width' => 1920,
            'max_height' => 1080,
        ],
        'mobile_image' => [
            'width' => 360,
            'height' => 640,
            'max_width' => 768,
            'max_height' => 1280,
        ],
    ],
    'uploads' => [
        'directory' => 'images/modules/carousel',
        'max_size_kb' => 5120,
        'accepted_mime_types' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
        ],
    ],
];
