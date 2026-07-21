<?php

declare(strict_types=1);

use App\Enums\Order\PaymentMethodEnum;

return [
    'name' => 'BankTransfer',
    'description' => 'Bank transfer checkout payment method singleton.',
    'payment_method' => PaymentMethodEnum::BankTransfer->value,
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\BankTransfer\\Filament\\Pages\\BankTransferSettingsPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
    ],
];
