<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Categories',

    'tabs' => [
        'general'           => 'General settings',
        'for_admin'         => 'For admin',
        'sorting'           => 'Products sorting',
        'localized_content' => 'Localized content',
    ],

    'labels' => [
        'products_per_page_limit'          => 'Products per page limit',
        'is_ajax_products_loading_enabled' => 'Enable AJAX products loading',
        'product_image_width'              => 'Product image width',
        'product_image_height'             => 'Product image height',
        'category_upload_max_size_mb'      => 'Category image upload max size (MB)',
        'category_image_upload_directory'  => 'Category image upload directory',
        'category_no_image_path'           => 'Category fallback no-image',
        'category_preview_list_width'      => 'Category preview width in list (admin)',
        'category_preview_list_height'     => 'Category preview height in list (admin)',
        'category_preview_page_width'      => 'Category preview width in page (admin)',
        'category_preview_page_height'     => 'Category preview height in page (admin)',
        'model'                            => 'Category page settings',
        'plural_model'                     => 'Category page settings',
        'is_sorting_enabled'               => 'Enable product sorting',
        'sorting_items'                    => 'Sorting options',
        'code'                             => 'Code',
        'is_enabled'                       => 'Enabled',
        'sort_order'                       => 'Sort order',
        'get_key'                          => 'GET key',
        'get_value'                        => 'GET value',
        'key'                              => 'Key',
        'value'                            => 'Value',
        'sorting_title'                    => 'Sorting block title',
        'sorting_description'              => 'Sorting block description',
        'option_labels'                    => 'Option labels',
        'option_label_value'               => 'Option label',
    ],

    'actions' => [
        'open_wiki' => 'Open settings wiki',
    ],

    'helpers' => [
        'category_no_image_path'          => 'Image used when category has no own image.',
        'category_image_upload_directory' => 'Use placeholders {year} and {month} (for example: images/categories/{year}/{month}).',
    ],
];
