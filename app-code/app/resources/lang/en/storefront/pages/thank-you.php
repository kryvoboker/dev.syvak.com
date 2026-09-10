<?php

declare(strict_types=1);

return [
    'heading' => 'Order completed',
    'not_found' => [
        'heading' => 'Order not found',
        'message' => 'We could not find order #:number. Please check the link and try again.',
    ],
    'labels' => [
        'order_number' => 'Your order #:number',
        'quantity' => 'Quantity',
        'additional_products' => 'Additional products',
        'payment_method' => 'Payment method',
        'delivery_method' => 'Delivery method',
        'delivery_address' => 'Delivery address',
        'subtotal' => 'Items subtotal',
        'promo_code_discount' => 'Promo code discount',
        'packaging' => 'Gift packaging',
        'delivery_cost' => 'Delivery',
        'total' => 'Total',
    ],
    'buttons' => [
        'show_all_products' => 'Show all products (:count)',
        'hide_products' => 'Hide products',
    ],
    'fallbacks' => [
        'product_name' => 'Product',
        'notes' => 'Your order will be shipped within 2–3 business days.',
        'tracking' => 'You will receive an SMS with the tracking number after dispatch.',
        'contact' => 'If you have any questions, we are always available.',
    ],
];
