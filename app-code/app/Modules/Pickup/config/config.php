<?php

declare(strict_types=1);

return [
    'name' => 'Pickup',
    'description' => 'Pickup store checkout delivery module with localized address and Google Maps embed.',
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\Pickup\\Filament\\Pages\\PickupSettingsPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
        'storefront' => [
            'data_service' => 'Services\\Storefront\\PickupStorefrontService',
            'view' => 'pickup::storefront.module',
            'view_data_key' => 'pickup_module_data',
        ],
    ],
    'settings' => [
        'default_page_types' => [
            'checkout',
        ],
    ],
];
