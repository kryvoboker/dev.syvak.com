<?php

declare(strict_types=1);

return [
    // Texts
    'texts'        => [
        'sku'                         => 'Артикул: :sku',
        'products_not_found'          => 'Товари не знайдено!',
        'category_title_fallback'     => 'Колекції',
        'category_products_not_found' => 'Товари для цієї категорії не знайдені',
        'category_products_in_stock'  => 'В наявності: :quantity',
        'from'                        => 'від',
        'to'                          => 'до',
        'of'                          => 'з',
        'filter'                      => 'Фільтр',
        'filter_results'              => 'Знайдено %d товарів',
    ],

    // Links
    'links'        => [
        'home'       => 'Головна',
        'previous'   => 'Попередня',
        'next'       => 'Наступна',
        'go_to_page' => 'Перейти на сторінку :page',
    ],

    // Buttons
    'buttons'      => [
        'catalog'   => 'Каталог',
        'show_more' => 'Показати ще',
        'filter'    => 'Фільтр',
        'sort'      => 'Сортування',
        'apply'     => 'Застосувати',
        'clear_all' => 'Очистити все',
    ],

    // Sort labels
    'sort'         => [
        'default'     => 'За замовчуванням',
        'newest'      => 'Спочатку нові',
        'bestsellers' => 'Бестселери',
        'price_asc'   => 'Спочатку дешеві',
        'price_desc'  => 'Спочатку дорогі',
    ],

    // Placeholders
    'placeholders' => [
        'search' => 'Пошук...',
    ],

    // Aria-Labels
    'aria_labels'  => [
        'toggle_main_menu'       => 'Перемкнути головне меню',
        'toggle_catalog_menu'    => 'Перемкнути меню каталогу',
        'back_to_main_mob_menu'  => 'Повернутися до головного меню',
        'close_mob_search'       => 'Закрити мобільний пошук',
        'close_mob_main_menu'    => 'Закрити мобільне головне меню',
        'category_products_list' => 'Список товарів категорії',
        'add_product_to_cart'    => 'Додати товар у кошик',
    ],

    // Errors
    'errors'       => [
        'keyword_required'   => 'Пошукове слово є обов\'язковим!',
        'keyword_string'     => 'Пошукове слово повинно бути рядком!',
        'keyword_min'        => 'Пошук повинен містити щонайменше 3 символи!',
        'keyword_max'        => 'Пошукове слово не може бути довшим за 255 символів!',
        'price_from'         => 'Значення "Ціна від" повинно бути меншим за значення "Ціна до"!',
        'filtering_products' => 'Під час фільтрації товарів сталася помилка. Будь ласка, спробуйте ще раз.',
    ],
];
