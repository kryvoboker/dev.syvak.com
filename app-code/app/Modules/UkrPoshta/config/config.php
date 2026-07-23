<?php

declare(strict_types=1);

return [
    'name' => 'UkrPoshta',
    'description' => 'UkrPoshta checkout data module with singleton configuration, chunked sync, and storefront rendering.',
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\UkrPoshta\\Filament\\Pages\\UkrPoshtaSyncPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
        'storefront' => [
            'data_service' => 'Services\\Storefront\\UkrPoshtaStorefrontService',
            'view' => 'ukrposhta::storefront.module',
            'view_data_key' => 'ukr_poshta_module_data',
        ],
    ],
    'settings' => [
        'default_language' => 'UA',
        'default_page_types' => [
            'checkout',
        ],
    ],
    'api' => [
        'url' => env('UKR_POSHTA_API_URL') ?? 'https://www.ukrposhta.ua/address-classifier-ws/',
        'timeout' => (int) (env('UKR_POSHTA_TIMEOUT') ?? 30),
        'language' => env('UKR_POSHTA_LANGUAGE') ?? 'UA',
        'rate_limit_delay' => (int) (env('UKR_POSHTA_RATE_LIMIT_DELAY') ?? 1), // seconds
        'wait_timeout' => (int) env('UKR_POSHTA_WAIT_TIMEOUT, 1'), // seconds
    ],
];
