<?php

declare(strict_types=1);

use App\Enums\Order\PaymentMethodEnum;

return [
    'name' => 'PaymentUponDelivery',
    'description' => 'Payment upon delivery checkout payment method singleton.',
    'payment_method' => PaymentMethodEnum::PaymentUponDelivery->value,
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
