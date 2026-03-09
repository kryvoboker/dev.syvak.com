<?php

declare(strict_types=1);

return [
    'labels' => [
        'model'         => 'Налаштування модуля',
        'plural_model'  => 'Налаштування модулів',
        'name'          => 'Назва',
        'placement'     => 'Розміщення',
        'context_key'   => 'Ключ контексту',
        'is_enabled'    => 'Увімкнений',
        'sort_order'    => 'Порядок сортування',
        'settings'      => 'Налаштування',
        'meta'          => 'Мета-дані',
        'setting_key'   => 'Ключ',
        'setting_value' => 'Значення',
    ],
    'columns' => [
        'name'        => 'Назва',
        'placement'   => 'Розміщення',
        'context_key' => 'Ключ контексту',
        'is_enabled'  => 'Увімкнений',
        'sort_order'  => 'Порядок сортування',
    ],
    'filters' => [
        'is_enabled'    => 'Стан',
        'enabled_only'  => 'Тільки увімкнені',
        'disabled_only' => 'Тільки вимкнені',
    ],
    'actions' => [
        'add'       => 'Додати',
        'duplicate' => 'Дублювати',
    ],
    'placeholders' => [
        'placement'   => 'home.hero',
        'context_key' => 'product.card',
    ],
    'notifications' => [
        'created'    => 'Екземпляр модуля створено.',
        'duplicated' => 'Екземпляр модуля дубльовано.',
    ],
    'pages' => [
        'create_title' => 'Створення налаштувань для модуля ":module"',
        'edit_title'   => 'Редагування налаштувань модуля ":module"',
    ],
];
