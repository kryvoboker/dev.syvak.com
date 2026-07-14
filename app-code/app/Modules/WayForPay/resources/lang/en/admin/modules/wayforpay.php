<?php

declare(strict_types=1);

return [
    'title' => 'WayForPay',
    'navigation_label' => 'WayForPay',
    'description' => 'Configure the WayForPay payment method.',
    'sections' => [
        'credentials' => [
            'title' => 'Gateway settings',
            'description' => 'These credentials are used only on the server to create signed payment requests.',
        ],
        'names' => [
            'title' => 'Checkout names',
            'description' => 'Set the payment method name for every active store language.',
        ],
    ],
    'labels' => [
        'merchant_account' => 'Merchant account',
        'secret_key' => 'Secret key',
        'merchant_domain_name' => 'Merchant domain',
        'checkout_widget_enabled' => 'Pay on the checkout page',
        'merchant_auth_type' => 'Merchant authentication type',
        'merchant_transaction_type' => 'Transaction type',
        'merchant_transaction_secure_type' => 'Secure transaction type',
        'api_version' => 'API version',
        'language' => 'Payment page language',
        'payment_systems' => 'Payment systems',
        'payment_name' => 'Checkout payment name',
        'callback_handler_method' => 'WayForPay response handler method',
    ],
    'helpers' => [
        'secret_key' => 'The value is never sent to the browser or written to logs. Leave it unchanged when editing other settings.',
        'checkout_widget_enabled' => 'When enabled, the customer stays on checkout and pays through the payment widget. When disabled, the customer is redirected to the payment service page. If the widget fails, redirect fallback is used automatically.',
        'payment_systems' => 'Optional semicolon-separated values, for example: card;googlePay;applePay.',
        'payment_name' => 'This name is displayed in the checkout payment accordion.',
        'callback_handler_method' => 'This method receives the server response when WayForPay sends a payment status notification. It can also be used when the payment widget reports a problem.',
    ],
    'actions' => [
        'save_settings' => 'Save settings',
    ],
    'validation' => [
        'name_required' => 'A payment name is required for :language.',
        'payment_systems_invalid' => 'Unsupported payment systems: :systems.',
        'save_failed' => 'The WayForPay settings could not be saved.',
    ],
    'notifications' => [
        'settings_saved' => 'WayForPay settings were saved.',
    ],
];
