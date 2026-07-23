<?php

declare(strict_types=1);

return [
    'name' => 'NovaPoshta',
    'description' => 'Nova Poshta checkout data module with localized sync and storefront rendering.',
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\NovaPoshta\\Filament\\Pages\\NovaPoshtaSyncPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
        'storefront' => [
            'data_service' => 'Services\\Storefront\\NovaPoshtaStorefrontService',
            'view' => 'novaposhta::storefront.module',
            'view_data_key' => 'nova_poshta_module_data',
        ],
    ],
    'settings' => [
        'default_language' => 'UA',
        'default_page_types' => [
            'checkout',
        ],
    ],
    'api' => [
        'url' => env('NOVA_POSHTA_API_URL') ?? 'https://api.novaposhta.ua/v2.0/json/',
        'timeout' => (int)(env('NOVA_POSHTA_TIMEOUT') ?? 30), // seconds
        'limit' => (int)(env('NOVA_POSHTA_LIMIT') ?? 500),
        'language' => env('NOVA_POSHTA_LANGUAGE') ?? 'UA',
        'wait_timeout' => (int) env('NOVA_POSHTA_WAIT_TIMEOUT, 1'), // seconds
    ],
];
