<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Валюти',

    // Labels
    'labels' => [
        'model' => 'Валюта',
        'plural_model' => 'Валюти',
        'code' => 'Код валюти',
        'name' => 'Назва валюти',
        'format_locale' => 'Локаль форматування',
        'symbol_left' => 'Символ зліва',
        'symbol_right' => 'Символ справа',
        'decimal_places' => 'Десяткові знаки',
        'exchange_rate' => 'Обмінний курс',
    ],

    // Helpers
    'helpers' => [
        'name' => 'Повна назва валюти (наприклад, Долар США, Євро, Українська гривня)',
        'code' => 'Код ISO 4217 (наприклад, USD, EUR, UAH)',
        'format_locale' => 'Локаль для форматування валюти (наприклад, en_US, de_DE)',
        'symbol_left' => 'Символ, що відображається ліворуч від суми (наприклад, $)',
        'symbol_right' => 'Символ, що відображається праворуч від суми (наприклад, €)',
        'decimal_places' => 'Кількість десяткових знаків для відображення (наприклад, 2 для центів)',
        'exchange_rate' => 'Обмінний курс відносно валюти за замовчуванням (наприклад, 1.000000)',
        'is_active' => 'Увімкнути цю валюту для користувачів',
        'is_default' => 'Встановити як валюту за замовчуванням для системи',
    ],

    // Columns
    'columns' => [
        'symbol_left' => 'Символ зліва',
        'symbol_right' => 'Символ справа',
        'decimal_places' => 'Десяткові знаки',
        'exchange_rate' => 'Обмінний курс',
    ],

    // Actions
    'actions' => [
        'update_rates' => 'Оновити курси',
        'modal_update_rates_title' => 'Оновити курси валют',
        'modal_update_rates_body' => 'Система запросить нові курси обміну і оновить активні валюти.',
    ],

    // Notifications
    'notifications' => [
        'rates_updated_body' => 'Курси обміну успішно оновлені.',
    ],

    // Text
    'texts' => [
        'cant_delete_default_currency' => 'Неможливо видалити валюту за замовчуванням',
        'cant_delete_last_active_currency' => 'Неможливо видалити останню активну валюту',
    ],

    // Error
    'errors' => [
        'cant_delete_default_currency' => 'Будь ласка, встановіть іншу валюту за замовчуванням перед видаленням цієї!',
        'cant_delete_last_active_currency' => 'У системі має залишатися щонайменше одна активна валюта!',
        'failed_to_update_rates' => 'Не вдалося оновити курси валют. Спробуйте пізніше!',
        'absent_default_currency' => 'У системі має бути встановлена валюта за замовчуванням!',
    ],
];
