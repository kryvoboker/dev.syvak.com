<?php

declare(strict_types=1);

return [
    // Texts
    'texts' => [
        'empty_cart' => 'The cart is empty for now ;(',
    ],

    'checkout' => [
        'title' => 'Checkout',
        'summary_title' => 'Your order',
        'continue_shopping' => 'Continue shopping',
        'delivery_section_title' => 'Delivery methods',
        'payment_section_title' => 'Payment methods',
        'labels' => [
            'first_name' => 'First name*',
            'last_name' => 'Last name*',
            'phone' => 'Phone*',
            'email' => 'E-mail',
            'city' => 'City*',
            'delivery_method' => 'Delivery method*',
            'delivery_address' => 'Delivery address*',
            'branch' => 'Branch*',
            'no_call' => 'Do not call to confirm the order',
            'comment' => 'Add a comment to the order',
            'promo' => 'Have a promo code / certificate',
            'comment_field' => 'Your comment',
            'promo_field' => 'Promo code or certificate',
        ],
        'placeholders' => [
            'city_search' => 'Search city',
            'delivery_methods' => 'Choose delivery method',
            'delivery_address' => 'Street, building, apartment',
            'branch_search' => 'Search branch',
            'payment_methods' => 'Choose payment method',
            'payment_placeholder' => 'No payment methods are available yet',
        ],
        'delivery_methods' => [
            'nova_poshta' => 'Nova Poshta',
            'nova_poshta_courier' => 'Nova Poshta (Courier)',
            'nova_poshta_poshtomat' => 'Nova Poshta (Poshtomat)',
            'ukr_poshta' => 'Ukr Poshta',
        ],
        'buttons' => [
            'show_all_items' => 'Show all items (:count)',
            'find_on_map' => 'Find on map',
            'edit_items' => 'Edit items',
            'submit' => 'Place order',
        ],
        'warnings' => [
            'choose_city_first' => 'Choose a city first',
            'no_delivery_methods' => 'No delivery methods are available for the selected city',
            'no_cities_found' => 'No cities found',
        ],
        'texts' => [
            'consent' => 'By placing an order, you confirm your agreement with the terms of service and privacy policy.',
            'subtotal' => 'Subtotal',
            'delivery' => 'Delivery',
            'total' => 'Total',
        ],
    ],
];
