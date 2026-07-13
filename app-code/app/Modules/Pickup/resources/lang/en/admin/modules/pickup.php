<?php

declare(strict_types=1);

return [
    'title' => 'Pickup from store',
    'description' => 'Configure the store address and Google Maps embed displayed for store pickup at checkout.',
    'navigation_label' => 'Pickup from store',
    'sections' => [
        'settings' => [
            'title' => 'Store pickup settings',
            'description' => 'Every active language must have an address. A safe Google Maps iframe can be added optionally.',
        ],
    ],
    'labels' => [
        'store_address' => 'Store address',
        'map_iframe' => 'Google Maps iframe HTML',
    ],
    'helpers' => [
        'store_address' => 'Enter the address that customers should see for this language.',
        'map_iframe' => 'Optionally paste Google Maps iframe HTML. Only a single HTTPS Google Maps embed iframe is saved.',
    ],
    'actions' => [
        'save_settings' => 'Save settings',
    ],
    'validation' => [
        'address_required' => 'The store address is required for :language.',
        'map_iframe_invalid' => 'Enter a valid and safe Google Maps iframe.',
    ],
    'notifications' => [
        'settings_saved' => 'Pickup store settings have been saved.',
    ],
];
