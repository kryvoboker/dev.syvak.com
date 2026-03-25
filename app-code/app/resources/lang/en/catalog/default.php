<?php

declare(strict_types=1);

return [
    // Texts
    'texts' => [
        'sku'                         => 'SKU: :sku',
        'products_not_found'          => 'Products not found!',
        'category_title_fallback'     => 'Collections',
        'category_products_not_found' => 'No products were found for this category',
        'category_products_in_stock'  => 'In stock: :quantity',
    ],

    // Links
    'links' => [
        'home' => 'Home',
    ],

    // Buttons
    'buttons' => [
        'catalog'   => 'Catalog',
        'show_more' => 'Show more',
        'filter'    => 'Filter',
        'sort'      => 'Sorting',
    ],

    // Sort labels
    'sort' => [
        'default'     => 'Default',
        'newest'      => 'Newest first',
        'bestsellers' => 'Bestsellers',
        'price_asc'   => 'Lowest price first',
        'price_desc'  => 'Highest price first',
    ],

    // Placeholders
    'placeholders' => [
        'search' => 'Search...',
    ],

    // Aria-Labels
    'aria_labels' => [
        'toggle_main_menu'       => 'Toggle main menu',
        'toggle_catalog_menu'    => 'Toggle catalog menu',
        'back_to_main_mob_menu'  => 'Back to main menu',
        'close_mob_search'       => 'Close mobile search',
        'close_mob_main_menu'    => 'Close mobile main menu',
        'category_products_list' => 'Category products list',
        'add_product_to_cart'    => 'Add product to cart',
    ],

    // Errors
    'errors' => [
        'keyword_required' => 'Search keyword is required!',
        'keyword_string'   => 'Search keyword must be a string!',
        'keyword_min'      => 'Search must be at least 3 characters long!',
        'keyword_max'      => 'Search keyword may not be greater than 255 characters!',
    ],
];
