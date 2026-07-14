<?php

declare(strict_types=1);

return [
    'name' => 'PaymentUponDelivery',
    'description' => 'Payment upon delivery checkout payment method singleton.',
    'payment_method' => 'payment_upon_delivery',
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\PaymentUponDelivery\\Filament\\Pages\\PaymentUponDeliverySettingsPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
    ],
];
