<?php

declare(strict_types=1);

use App\Enums\Order\PaymentMethodEnum;

return [
    'name' => 'WayForPay',
    'identifiers' => [
        'payment_method' => PaymentMethodEnum::WayForPay->value,
        'translation_key' => 'wayforpay::storefront/checkout.payment_methods.wayforpay',
    ],
    'endpoints' => [
        'payment' => 'https://secure.wayforpay.com/pay',
        'widget_script' => 'https://secure.wayforpay.com/server/pay-widget.js',
    ],
    'callback' => [
        'route_name' => 'localized.catalog.wayforpay.callback',
        'handler_method' => 'WayForPayCallbackController::__invoke',
    ],
    'return' => [
        'route_name' => 'localized.catalog.wayforpay.return',
        'handler_method' => 'WayForPayReturnController::__invoke',
    ],
    'storage' => [
        'settings_global_config_key' => 'wayforpay.settings',
        'payment_names_global_config_key' => 'wayforpay.payment_names',
    ],
    'admin' => [
        'can_create_instances' => false,
        'module_list_action' => [
            'page' => 'Modules\\WayForPay\\Filament\\Pages\\WayForPaySettingsPage',
        ],
    ],
    'runtime' => [
        'provider_loading_strategy' => config('modules-runtime.allowed_strategies.route_matched'),
    ],
    'settings' => [
        'defaults' => [
            'merchant_auth_type' => 'SimpleSignature',
            'merchant_transaction_type' => 'AUTO',
            'merchant_transaction_secure_type' => 'AUTO',
            'api_version' => '1',
            'language' => 'UA',
            'checkout_widget_enabled' => true,
        ],
        'options' => [
            'merchant_auth_types' => [
                'SimpleSignature',
                'ticket',
                'password',
            ],
            'transaction_types' => [
                'AUTO',
                'AUTH',
                'SALE',
            ],
            'secure_transaction_types' => [
                'AUTO',
            ],
            'api_versions' => [
                '1',
                '2',
            ],
            'languages' => [
                'AUTO',
                'UA',
                'RU',
                'EN',
                'DE',
                'ES',
                'IT',
                'PL',
                'FR',
                'RO',
                'LV',
                'SK',
                'CS',
            ],
            'payment_systems' => [
                'card',
                'googlePay',
                'applePay',
                'privat24',
                'lpTerminal',
                'delay',
                'bankCash',
                'qrCode',
                'masterPass',
                'visaCheckout',
                'bot',
                'payParts',
                'payPartsMono',
                'payPartsPrivat',
                'payPartsAbank',
                'instantAbank',
                'globusPlus',
                'payPartsOtp',
                'OnusInstallment',
            ],
        ],
    ],
    'request' => [
        'redirect_method' => 'POST',
        'client_country' => 'Ukraine',
    ],
];
