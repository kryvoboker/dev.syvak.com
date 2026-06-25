<?php

declare(strict_types=1);

return [
    // Texts
    'texts'        => [
        'sku'                         => 'SKU: :sku',
        'products_not_found'          => 'Products not found!',
        'category_title_fallback'     => 'Collections',
        'category_products_not_found' => 'No products were found for this category',
        'category_products_in_stock'  => 'In stock: :quantity',
        'from'                        => 'from',
        'to'                          => 'to',
        'of'                          => 'of',
        'filter'                      => 'Filter',
        'filter_results'              => '%d products found',
    ],

    // Links
    'links'        => [
        'home'       => 'Home',
        'previous'   => 'Previous',
        'next'       => 'Next',
        'go_to_page' => 'Go to page :page',
    ],

    // Buttons
    'buttons'      => [
        'catalog'   => 'Catalog',
        'show_more' => 'Show more',
        'filter'    => 'Filter',
        'sort'      => 'Sorting',
        'apply'     => 'Apply',
        'clear_all' => 'Clear all',
    ],

    // Sort labels
    'sort'         => [
        'default'     => 'Default',
        'newest'      => 'Newest first',
        'bestsellers' => 'Bestsellers',
        'price-asc'   => 'Lowest price first',
        'price-desc'  => 'Highest price first',
    ],

    // Placeholders
    'placeholders' => [
        'search' => 'Search...',
    ],

    // Aria-Labels
    'aria_labels'  => [
        'toggle_main_menu'       => 'Toggle main menu',
        'toggle_catalog_menu'    => 'Toggle catalog menu',
        'back_to_main_mob_menu'  => 'Back to main menu',
        'close_mob_search'       => 'Close mobile search',
        'close_mob_main_menu'    => 'Close mobile main menu',
        'category_products_list' => 'Category products list',
        'add_product_to_cart'    => 'Add product to cart',
    ],

    // Errors
    'errors'       => [
        'keyword_required'   => 'Search keyword is required!',
        'keyword_string'     => 'Search keyword must be a string!',
        'keyword_min'        => 'Search must be at least 3 characters long!',
        'keyword_max'        => 'Search keyword may not be greater than 255 characters!',
        'price_from'         => 'The "Price from" value must be less than "Price to"!',
        'filtering_products' => 'An error occurred while filtering products. Please try again later.',
    ],

    // Product page
    'product'      => [
        'option_groups' => [
            'color'              => 'Color',
            'length'             => 'Length, cm',
            'size'               => 'Size',
            'attribute_fallback' => 'Attribute',
        ],
        'details'       => [
            'composition' => 'Composition:',
            'care'        => 'Care:',
        ],
        'labels'        => [
            'size_help'     => 'Check your size',
            'buy_one_click' => 'Buy in one click',
            'add_to_cart'   => 'Add to cart',
            'notify'        => 'Notify when available',
            'telegram'      => 'Telegram',
        ],
    ],

    'cart' => [
        'labels'   => [
            'cart'                    => 'Cart',
            'fast_order'              => 'Fast order',
            'empty'                   => 'Your cart is empty',
            'quantity'                => 'Qty',
            'selected_items'          => 'Selected :selected of :total',
            'selected_items_in_modal' => 'Selected %d of %d',
            'total'                   => 'Total',
            'first_name'              => 'First name',
            'last_name'               => 'Last name',
            'phone'                   => 'Phone',
        ],
        'buttons'  => [
            'continue_shopping' => 'Continue shopping',
            'checkout'          => 'Checkout',
            'submit_fast_order' => 'Submit fast order',
            'show_more_items'   => 'Show other items (:count)',
            'hide_more_items'   => 'Hide other items',
        ],
        'totals'   => [
            'items_subtotal' => 'Items subtotal',
            'grand_total'    => 'Grand total',
        ],
        'validation' => [
            'first_name_required' => 'First name is required.',
            'first_name_min'      => 'First name must contain at least :min characters.',
            'last_name_required'  => 'Last name is required.',
            'last_name_min'       => 'Last name must contain at least :min characters.',
            'phone_required'      => 'Phone is required.',
            'phone_min'           => 'Phone must contain at least :min digits.',
        ],
        'messages' => [
            'variant_not_found' => 'Product variant was not found.',
            'item_added'        => 'Product was added to cart.',
            'item_updated'      => 'Cart item was updated.',
            'item_removed'      => 'Cart item was removed.',
            'cart_is_empty'     => 'Cart is empty.',
            'payment_failed'    => 'Payment failed. Please try again.',
        ],
    ],
];
