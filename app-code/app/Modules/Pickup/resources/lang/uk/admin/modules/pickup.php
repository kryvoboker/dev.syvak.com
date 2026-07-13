<?php

declare(strict_types=1);

return [
    'title' => 'Самовивіз з магазину',
    'description' => 'Налаштуйте адресу магазину та карту Google Maps для самовивозу на сторінці оформлення замовлення.',
    'navigation_label' => 'Самовивіз з магазину',
    'sections' => [
        'settings' => [
            'title' => 'Налаштування самовивозу',
            'description' => 'Для кожної активної мови потрібно вказати адресу. Безпечний iframe Google Maps можна додати за бажанням.',
        ],
    ],
    'labels' => [
        'store_address' => 'Адреса магазину',
        'map_iframe' => 'HTML iframe Google Maps',
    ],
    'helpers' => [
        'store_address' => 'Вкажіть адресу, яку побачить покупець для цієї мови.',
        'map_iframe' => 'За бажанням вставте HTML iframe з Google Maps. Буде збережено лише один безпечний iframe HTTPS-карти Google Maps.',
    ],
    'actions' => [
        'save_settings' => 'Зберегти налаштування',
    ],
    'validation' => [
        'address_required' => 'Адреса магазину обов’язкова для мови :language.',
        'map_iframe_invalid' => 'Вкажіть коректний і безпечний iframe Google Maps.',
    ],
    'notifications' => [
        'settings_saved' => 'Налаштування самовивозу збережено.',
    ],
];
