<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Сторінка товару',

    'tabs' => [
        'for_customer' => 'Для покупця',
        'for_admin' => 'Для адмінки',
    ],

    'labels' => [
        'model' => 'Налаштування сторінки товару',
        'plural_model' => 'Налаштування сторінки товару',
        'minimum_stock_quantity' => 'Мінімальна кількість на складі',
        'ean_max_length' => 'Максимальна довжина EAN',
        'product_image_width' => 'Ширина зображення товару',
        'product_image_height' => 'Висота зображення товару',
        'upload_max_size_mb' => 'Максимальний розмір завантаження (МБ)',
        'image_upload_directory' => 'Директорія завантаження зображень',
        'no_image_path' => 'Fallback-зображення',
        'preview_list_image_width' => 'Ширина превʼю в списку (адмінка)',
        'preview_list_image_height' => 'Висота превʼю в списку (адмінка)',
        'preview_page_image_width' => 'Ширина превʼю на сторінці (адмінка)',
        'preview_page_image_height' => 'Висота превʼю на сторінці (адмінка)',
    ],

    'helpers' => [
        'no_image_path' => 'Зображення, яке використовується, якщо у товару немає власного.',
        'image_upload_directory' => 'Використовуйте плейсхолдери {year} і {month} для динамічних папок (наприклад: images/products/{year}/{month}).',
    ],
];
