<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'date_format' => env('APP_DATE_FORMAT', 'Y-m-d'),
    'time_format' => env('APP_TIME_FORMAT', 'H:i:s'),
    'datetime_format' => env('APP_DATETIME_FORMAT', 'Y-m-d H:i:s'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),
    'allowed_locales' => string_to_array(env('APP_ALLOWED_LOCALES', 'en')),
    'default_locale' => env('APP_DEFAULT_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => string_to_array((string) env('APP_PREVIOUS_KEYS', '')),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'admin_emails_for_access' => string_to_array(env('ADMIN_EMAILS_FOR_ACCESS', '')),

    'denied_delete_emails' => [
        'fast.kamaz@gmail.com',
    ],
    'modules_placements' => [
        'top' => 'Top',
        'bottom' => 'Bottom',
    ],
    'page_settings' => [
        'category' => [
            'products_per_page_limit' => 20,
            'ajax_products_loading_enabled' => true,
            'product_image_width' => 420,
            'product_image_height' => 420,
            'filter_modes' => [
                'range' => 'Range',
                'boolean' => 'Boolean',
                'multiple' => 'Multiple',
            ],
        ],
        'product' => [
            'minimum_stock_quantity' => (int) env('PRODUCT_MINIMUM_STOCK_QUANTITY', 1),
            'ean_max_length' => (int) env('PRODUCT_EAN_MAX_LENGTH', 13),
            'image_width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
            'image_height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            'for_customer' => [
                'minimum_stock_quantity' => (int) env('PRODUCT_MINIMUM_STOCK_QUANTITY', 1),
                'image_width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
                'image_height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            ],
            'for_admin' => [
                'ean_max_length' => (int) env('PRODUCT_EAN_MAX_LENGTH', 13),
                'upload_max_size_kb' => (int) env('MAX_UPLOAD_PRODUCT_IMAGE_SIZE_KB', 5120),
                'image_upload_directory' => env('PRODUCTS_IMAGES_PATH', 'images/products') . '/' . date('Y/m'),
                'no_image' => env('DEFAULT_PRODUCT_NO_IMAGE_PATH', 'images/no-image.png'),
                'preview_in_list_width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_WIDTH', 100),
                'preview_in_list_height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_HEIGHT', 100),
                'preview_in_page_width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
                'preview_in_page_height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            ],
        ],
        'search' => [
            'products_per_page_limit' => (int) env('SEARCH_PRODUCTS_PER_PAGE', 15),
            'images' => [
                'search_product' => [
                    'width' => 219,
                    'height' => 219,
                ],
                'search_not_found' => [
                    'path' => env('DEFAULT_IMAGE_SEARCH_NOT_FOUND_PATH', 'images/search/not-found.jpg'),
                    'width' => 600,
                    'height' => 600,
                ],
            ],
        ],
        'not_found' => [
            'for_admin' => [
                'upload_max_size_kb' => (int) env('MAX_UPLOAD_404_PAGE_IMAGE_SIZE_KB', 5120),
            ],
        ],
        'failure' => [
            'for_admin' => [
                'upload_max_size_kb' => (int) env('MAX_UPLOAD_FAILURE_PAGE_IMAGE_SIZE_KB', 5120),
            ],
        ],
    ],
    'socials_list' => string_to_array(env('SOCIALS_LIST')),
    'regex_validate_conditions' => [
        'email' => '/^((?!\.)[\w\-_.]*[^.])(@\w+)(\.\w+(\.\w+)?[^.\W])$/',
        'telephone' => '/(^((\+?\d{2,}\s?)|(.*))\(?\d{3,}\)?\s?\d{3,}-?\d{2,}-?\d{2,}$)/',
        'password' => '/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\s:])(\S)+$/',
        'coordinates' => '/^-?\d{1,2}\.\d+,\s?-?\d{1,3}\.\d+$/',
    ],
    'currency' => [
        'json_url' => env('CURRENCY_JSON_URL'),
        'default_currency_code' => env('DEFAULT_CURRENCY_CODE'),
        'default_exchange_rate' => (float) env('DEFAULT_EXCHANGE_RATE'),
        'default_currency_symbol' => env('DEFAULT_CURRENCY_SYMBOL'),
        'default_format_locale' => env('DEFAULT_CURRENCY_FORMAT_LOCALE', 'en_US'),
        'default_decimal_places' => (int) env('DEFAULT_CURRENCY_DECIMAL_PLACES', 2),
        'default_decimal_separator' => env('DEFAULT_DECIMAL_SEPARATOR'),
        'default_thousand_separator' => env('DEFAULT_THOUSAND_SEPARATOR'),
    ],
    'images' => [
        'image_version' => env('IMAGE_VERSION'),
        'path_to_logo' => env('PATH_TO_LOGO_IMAGE'),
        'logo_width' => (int) env('LOGO_IMAGE_WIDTH'),
        'logo_height' => (int) env('LOGO_IMAGE_HEIGHT'),
        'default_no_image' => env('DEFAULT_NO_IMAGE_PATH'),
        'default_image_search_not_found' => env('DEFAULT_IMAGE_SEARCH_NOT_FOUND_PATH'),
        'prototype_quality' => (int) env('PROTOTYPE_IMAGE_QUALITY'),
        'webp_quality' => (int) env('WEBP_IMAGE_QUALITY'),
        'avif_quality' => (int) env('AVIF_IMAGE_QUALITY'),
        'total_sizes_for_generate' => (int) env('TOTAL_IMAGE_SIZES_FOR_GENERATE'),
        'max_image_width_for_convert' => (int) env('MAX_IMAGE_WIDTH_FOR_CONVERT'),
        'max_image_height_for_convert' => (int) env('MAX_IMAGE_HEIGHT_FOR_CONVERT'),
        'default_max_upload_image_size_kb' => (int) env('DEFAULT_MAX_UPLOAD_IMAGE_SIZE_KB'),
        'category' => [
            'upload' => [
                'max_size_kb' => (int) env('MAX_UPLOAD_CATEGORY_IMAGE_SIZE_KB', 5120), // 5 MB,
            ],
            'no_image' => env('DEFAULT_CATEGORY_NO_IMAGE_PATH'),
            'preview_in_list_in_admin' => [
                'width' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_WIDTH', 100),
                'height' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_HEIGHT', 100),
            ],
            'preview_in_page_in_admin' => [
                'width' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
                'height' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            ],
            'preview_in_page_in_catalog_menu' => [
                'width' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_PAGE_IN_CATALOG_WIDTH', 720),
                'height' => (int) env('CATEGORY_IMAGE_PREVIEW_IN_PAGE_IN_CATALOG_HEIGHT', 720),
            ],
            'image_path' => env('CATEGORIES_IMAGES_PATH', 'images/categories') . '/' . date('Y/m'),
        ],
        'product' => [
            'upload' => [
                'max_size_kb' => (int) env('MAX_UPLOAD_PRODUCT_IMAGE_SIZE_KB', 5120), // 5 MB,
            ],
            'no_image' => env('DEFAULT_PRODUCT_NO_IMAGE_PATH'),
            'preview_in_list_in_admin' => [
                'width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_WIDTH', 100),
                'height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_LIST_IN_ADMIN_HEIGHT', 100),
            ],
            'preview_in_page_in_admin' => [
                'width' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
                'height' => (int) env('PRODUCT_IMAGE_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            ],
            'image_path' => env('PRODUCTS_IMAGES_PATH', 'images/products') . '/' . date('Y/m'),
        ],
        'user' => [
            'upload' => [
                'max_size_kb' => (int) env('MAX_UPLOAD_USER_IMAGE_SIZE_KB', 5120), // 5 MB,
            ],
            'no_image' => env('DEFAULT_USER_NO_AVATAR_PATH'),
            'preview_in_list_in_admin' => [
                'width' => (int) env('USER_AVATAR_PREVIEW_IN_LIST_IN_ADMIN_WIDTH', 100),
                'height' => (int) env('USER_AVATAR_PREVIEW_IN_LIST_IN_ADMIN_HEIGHT', 100),
            ],
            'preview_in_page_in_admin' => [
                'width' => (int) env('USER_AVATAR_PREVIEW_IN_PAGE_IN_ADMIN_WIDTH', 500),
                'height' => (int) env('USER_AVATAR_PREVIEW_IN_PAGE_IN_ADMIN_HEIGHT', 500),
            ],
            'image_path' => env('AVATARS_PATH') . '/' . date('Y/m'),
        ],
    ],
    'products' => [
        'minimum_stock_quantity' => (int) env('PRODUCT_MINIMUM_STOCK_QUANTITY'),
        'search_products_per_page' => (int) env('SEARCH_PRODUCTS_PER_PAGE'),
        'ean_max_length' => (int) env('PRODUCT_EAN_MAX_LENGTH'),
    ],
    'frontend' => [
        'max_viewport_width' => (int) env('MAX_VIEWPORT_WIDTH'),
    ],

];
