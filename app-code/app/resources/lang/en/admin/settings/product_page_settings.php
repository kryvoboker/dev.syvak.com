<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Product Page',

    'tabs' => [
        'for_customer' => 'For customer',
        'for_admin' => 'For admin',
    ],

    'labels' => [
        'model' => 'Product page setting',
        'plural_model' => 'Product page settings',
        'minimum_stock_quantity' => 'Minimum stock quantity',
        'ean_max_length' => 'EAN max length',
        'product_image_width' => 'Product image width',
        'product_image_height' => 'Product image height',
        'upload_max_size_mb' => 'Upload max size (MB)',
        'image_upload_directory' => 'Image upload directory',
        'no_image_path' => 'Fallback no-image',
        'preview_list_image_width' => 'Preview width in list (admin)',
        'preview_list_image_height' => 'Preview height in list (admin)',
        'preview_page_image_width' => 'Preview width in page (admin)',
        'preview_page_image_height' => 'Preview height in page (admin)',
    ],

    'helpers' => [
        'no_image_path' => 'Image used when product has no own image.',
        'image_upload_directory' => 'Use placeholders {year} and {month} for dynamic folders (for example: images/products/{year}/{month}).',
    ],
];
