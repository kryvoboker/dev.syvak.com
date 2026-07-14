<?php

declare(strict_types=1);

return [
    'title' => 'WayForPay',
    'navigation_label' => 'WayForPay',
    'description' => 'Налаштування способу оплати WayForPay.',
    'sections' => [
        'credentials' => [
            'title' => 'Налаштування платіжного шлюзу',
            'description' => 'Ці дані використовуються лише на сервері для створення підписаних платіжних запитів.',
        ],
        'names' => [
            'title' => 'Назви на checkout',
            'description' => 'Вкажіть назву способу оплати для кожної активної мови магазину.',
        ],
    ],
    'labels' => [
        'merchant_account' => 'Merchant account',
        'secret_key' => 'Secret key',
        'merchant_domain_name' => 'Домен магазину',
        'checkout_widget_enabled' => 'Оплата зі сторінки оформлення замовлення',
        'merchant_auth_type' => 'Тип авторизації мерчанта',
        'merchant_transaction_type' => 'Тип транзакції',
        'merchant_transaction_secure_type' => 'Тип захищеної транзакції',
        'api_version' => 'Версія API',
        'language' => 'Мова платіжної сторінки',
        'payment_systems' => 'Платіжні системи',
        'payment_name' => 'Назва способу оплати на checkout',
        'callback_handler_method' => 'Метод обробки відповіді WayForPay',
    ],
    'helpers' => [
        'secret_key' => 'Значення не передається в браузер і не записується в логи. Під час редагування інших полів залиште його без змін.',
        'checkout_widget_enabled' => 'Якщо увімкнено, користувач залишається на checkout і сплачує через платіжний віджет. Якщо вимкнено, користувач переходить на сторінку платіжного сервісу. Якщо віджет не працює, автоматично використовується redirect fallback.',
        'payment_systems' => 'Необов’язкові значення через крапку з комою, наприклад: card;googlePay;applePay.',
        'payment_name' => 'Ця назва відображається в аккордеоні способів оплати checkout.',
        'callback_handler_method' => 'Цей метод приймає відповідь сервера, коли WayForPay надсилає повідомлення про статус платежу. Його також можна використовувати, якщо платіжний віджет повідомляє про проблему.',
    ],
    'actions' => [
        'save_settings' => 'Зберегти налаштування',
    ],
    'validation' => [
        'name_required' => 'Назва способу оплати обов’язкова для мови :language.',
        'payment_systems_invalid' => 'Непідтримувані платіжні системи: :systems.',
        'save_failed' => 'Не вдалося зберегти налаштування WayForPay.',
    ],
    'notifications' => [
        'settings_saved' => 'Налаштування WayForPay збережено.',
    ],
];
