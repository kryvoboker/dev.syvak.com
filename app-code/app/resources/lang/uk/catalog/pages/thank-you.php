<?php

declare(strict_types=1);

return [
    'heading' => 'Замовлення оформлене',
    'labels' => [
        'order_number' => 'Ваше замовлення №:number',
        'quantity' => 'Кількість',
        'additional_products' => 'Додаткові товари',
        'payment_method' => 'Спосіб оплати',
        'delivery_method' => 'Спосіб доставки',
        'delivery_address' => 'Адреса доставки',
        'subtotal' => 'Сума товарів',
        'packaging' => 'Подарункова упаковка',
        'delivery_cost' => 'Доставка',
        'total' => 'Підсумок',
    ],
    'buttons' => [
        'show_all_products' => 'Показати всі товари (:count)',
        'hide_products' => 'Приховати товари',
    ],
    'fallbacks' => [
        'product_name' => 'Товар',
        'notes' => 'Ваше замовлення буде відправлено протягом 2–3 робочих днів.',
        'tracking' => 'Після передачі посилки службі доставки ви отримаєте SMS з номером ТТН.',
        'contact' => 'Якщо виникнуть питання — ми завжди на зв’язку.',
    ],
];
