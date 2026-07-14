<?php

declare(strict_types=1);

return [
    'title' => 'Bank transfer',
    'description' => 'Configure the required payment name and optional payment information displayed at checkout.',
    'navigation_label' => 'Bank transfer',
    'sections' => [
        'settings' => [
            'title' => 'Bank transfer checkout settings',
            'description' => 'A payment name is required for every active language. Payment information is optional.',
        ],
    ],
    'labels' => [
        'payment_name' => 'Payment name',
        'payment_information' => 'Payment information',
    ],
    'helpers' => [
        'payment_name' => 'Enter the name customers should see for this payment method.',
        'payment_information' => 'Enter plain-text payment instructions for this language. HTML is not interpreted.',
    ],
    'actions' => [
        'save_settings' => 'Save settings',
    ],
    'validation' => [
        'name_required' => 'The payment name is required for :language.',
        'save_failed' => 'The payment information could not be saved. Please try again.',
    ],
    'notifications' => [
        'settings_saved' => 'Bank transfer settings have been saved.',
    ],
];
