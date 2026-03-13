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
    'products_carousel' => [
        'sections' => [
            'source_mode'           => 'Режим джерела товарів',
            'category_based_window' => 'Показати товари з категорій',
            'category_products'     => 'Вибрані товари з вибраних категорій',
            'manual_only_window'    => 'Тільки вибіркові товари',
        ],
        'labels' => [
            'module_name'                            => 'Назва модуля',
            'source_mode'                            => 'Режим джерела товарів',
            'categories_tree'                        => 'Дерево категорій',
            'use_selected_products_only'             => 'Показати тільки визначені товари',
            'search_products_in_selected_categories' => 'Живий пошук у вибраних категоріях',
            'search_all_active_products'             => 'Живий пошук серед усіх активних товарів',
            'selected_products'                      => 'Вибрані товари',
        ],
        'options' => [
            'source_mode' => [
                'category_based' => 'Показати товари з категорій',
                'manual_only'    => 'Тільки вибіркові товари',
            ],
        ],
        'actions' => [
            'select_all_categories' => 'Відмітити все',
            'clear_all_categories'  => 'Зняти всі відмітки',
        ],
        'helpers' => [
            'category_based_window'                  => 'Оберіть активні категорії в дереві. Дочірні категорії відображаються всередині батьківських.',
            'manual_only_window'                     => 'Оберіть будь-які активні товари з усього активного каталогу.',
            'search_products_in_selected_categories' => 'Введіть частину назви, моделі або SKU. Пошук працює тільки в межах вибраних категорій.',
            'search_all_active_products'             => 'Введіть частину назви, моделі або SKU. Пошук працює серед усіх активних товарів.',
            'selected_products'                      => 'Використовуйте toggle checkbox, щоб залишити або прибрати товари у фінальному списку модуля.',
        ],
    ],
];
