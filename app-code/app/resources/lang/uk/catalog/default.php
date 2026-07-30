<?php

declare(strict_types=1);

return [
    // Texts
    'texts' => [
        'sku' => 'Артикул: :sku',
        'products_not_found' => 'Товари не знайдено!',
        'category_title_fallback' => 'Колекції',
        'category_products_not_found' => 'Товари для цієї категорії не знайдені',
        'category_products_in_stock' => 'В наявності: :quantity',
        'from' => 'від',
        'to' => 'до',
        'of' => 'з',
        'filter' => 'Фільтр',
        'filter_results' => 'Знайдено %d товарів',
        'pcs' => ':pcs шт.',
    ],

    // Links
    'links' => [
        'home' => 'Головна',
        'previous' => 'Попередня',
        'next' => 'Наступна',
        'go_to_page' => 'Перейти на сторінку :page',
    ],

    // Buttons
    'buttons' => [
        'catalog' => 'Каталог',
        'show_more' => 'Показати ще',
        'filter' => 'Фільтр',
        'sort' => 'Сортування',
        'apply' => 'Застосувати',
        'clear_all' => 'Очистити все',
    ],

    // Sort labels
    'sort' => [
        'default' => 'За замовчуванням',
        'newest' => 'Спочатку нові',
        'bestsellers' => 'Бестселери',
        'price-asc' => 'Спочатку дешеві',
        'price-desc' => 'Спочатку дорогі',
    ],

    // Placeholders
    'placeholders' => [
        'search' => 'Пошук...',
    ],

    // Aria-Labels
    'aria_labels' => [
        'toggle_main_menu' => 'Перемкнути головне меню',
        'toggle_catalog_menu' => 'Перемкнути меню каталогу',
        'back_to_main_mob_menu' => 'Повернутися до головного меню',
        'close_mob_search' => 'Закрити мобільний пошук',
        'close_mob_main_menu' => 'Закрити мобільне головне меню',
        'close_pc_main_menu' => 'Закрити меню каталогу для ПК',
        'category_products_list' => 'Список товарів категорії',
        'add_product_to_cart' => 'Додати товар у кошик',
    ],

    // Errors
    'errors' => [
        'keyword_required' => 'Пошукове слово є обов\'язковим!',
        'keyword_string' => 'Пошукове слово повинно бути рядком!',
        'keyword_min' => 'Пошук повинен містити щонайменше 3 символи!',
        'keyword_max' => 'Пошукове слово не може бути довшим за 255 символів!',
        'price_from' => 'Значення "Ціна від" повинно бути меншим за значення "Ціна до"!',
        'filtering_products' => 'Під час фільтрації товарів сталася помилка. Будь ласка, спробуйте ще раз.',
    ],

    // Product page
    'product' => [
        'option_groups' => [
            'color' => 'Колір',
            'length' => 'Довжина, см',
            'size' => 'Розмір',
            'attribute_fallback' => 'Характеристика',
        ],
        'details' => [
            'composition' => 'Склад:',
            'care' => 'Догляд:',
        ],
        'labels' => [
            'size_help' => 'Перевір свій розмір',
            'buy_one_click' => 'Купити в 1 клік',
            'add_to_cart' => 'Додати в кошик',
            'notify' => 'Повідомити про наявність',
            'telegram' => 'Telegram',
        ],
    ],

    'cart' => [
        'labels' => [
            'cart' => 'Кошик',
            'fast_order' => 'Швидке замовлення',
            'empty' => 'Ваш кошик порожній',
            'quantity' => 'К-сть',
            'selected_items' => 'Вибрано :selected з :total',
            'selected_items_in_modal' => 'Вибрано %d з %d',
            'total' => 'Сума',
            'first_name' => 'Імʼя',
            'last_name' => 'Прізвище',
            'phone' => 'Телефон',
        ],
        'buttons' => [
            'continue_shopping' => 'Продовжити покупки',
            'checkout' => 'Оформити замовлення',
            'submit_fast_order' => 'Оформити швидке замовлення',
            'show_more_items' => 'Показати інші товари (:count)',
            'hide_more_items' => 'Приховати інші товари',
        ],
        'totals' => [
            'items_subtotal' => 'Сума товарів',
            'grand_total' => 'Разом до сплати',
        ],
        'validation' => [
            'first_name_required' => 'Імʼя є обовʼязковим.',
            'first_name_min' => 'Імʼя має містити щонайменше :min символи.',
            'last_name_required' => 'Прізвище є обовʼязковим.',
            'last_name_min' => 'Прізвище має містити щонайменше :min символи.',
            'phone_required' => 'Телефон є обовʼязковим.',
            'phone_min' => 'Телефон має містити щонайменше :min цифр.',
        ],
        'messages' => [
            'variant_not_found' => 'Варіант товару не знайдено.',
            'item_added' => 'Товар додано до кошика.',
            'item_updated' => 'Позицію в кошику оновлено.',
            'item_removed' => 'Позицію видалено з кошика.',
            'cart_is_empty' => 'Кошик порожній.',
            'payment_failed' => 'Оплата неуспішна. Спробуйте ще раз.',
        ],
    ],
];
