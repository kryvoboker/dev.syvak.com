<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Категорії',

    'tabs' => [
        'general'           => 'Загальні налаштування',
        'for_admin'         => 'Для адмінки',
        'sorting'           => 'Сортування товарів',
        'localized_content' => 'Локалізований контент',
    ],

    'labels' => [
        'products_per_page_limit'          => 'Ліміт товарів на сторінці',
        'is_ajax_products_loading_enabled' => 'Увімкнути AJAX підгрузку товарів',
        'product_image_width'              => 'Ширина зображення товару',
        'product_image_height'             => 'Висота зображення товару',
        'category_upload_max_size_mb'      => 'Макс. розмір завантаження зображення категорії (МБ)',
        'category_image_upload_directory'  => 'Директорія завантаження зображень категорії',
        'category_no_image_path'           => 'Fallback-зображення категорії',
        'category_preview_list_width'      => 'Ширина превʼю категорії в списку (адмінка)',
        'category_preview_list_height'     => 'Висота превʼю категорії в списку (адмінка)',
        'category_preview_page_width'      => 'Ширина превʼю категорії на сторінці (адмінка)',
        'category_preview_page_height'     => 'Висота превʼю категорії на сторінці (адмінка)',
        'model'                            => 'Налаштування сторінки категорій',
        'plural_model'                     => 'Налаштування сторінки категорій',
        'is_sorting_enabled'               => 'Увімкнути сортування товарів',
        'sorting_items'                    => 'Опції сортування',
        'code'                             => 'Код',
        'is_enabled'                       => 'Увімкнено',
        'sort_order'                       => 'Порядок сортування',
        'get_key'                          => 'GET ключ',
        'get_value'                        => 'GET значення',
        'key'                              => 'Ключ',
        'value'                            => 'Значення',
        'sorting_title'                    => 'Заголовок блоку сортування',
        'sorting_description'              => 'Опис блоку сортування',
        'option_labels'                    => 'Підписи опцій',
        'option_label_value'               => 'Підпис опції',
    ],

    'actions' => [
        'open_wiki' => 'Відкрити wiki налаштувань',
    ],

    'helpers' => [
        'category_no_image_path'          => 'Зображення, яке використовується, якщо у категорії немає власного.',
        'category_image_upload_directory' => 'Використовуйте плейсхолдери {year} і {month} (наприклад: images/categories/{year}/{month}).',
    ],
];
