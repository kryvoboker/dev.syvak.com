<?php

declare(strict_types=1);

return [
    // Texts
    'texts' => [
        'empty_cart' => 'Поки що кошик порожній ;(',
    ],

    'checkout' => [
        'title' => 'Оформлення замовлення',
        'summary_title' => 'Ваше замовлення',
        'continue_shopping' => 'Продовжити покупки',
        'delivery_section_title' => 'Способи доставки',
        'payment_section_title' => 'Способи оплати',
        'labels' => [
            'first_name' => 'Імʼя*',
            'last_name' => 'Прізвище*',
            'phone' => 'Телефон*',
            'email' => 'E-mail',
            'city' => 'Місто*',
            'delivery_method' => 'Спосіб доставки*',
            'delivery_address' => 'Адреса доставки*',
            'branch' => 'Відділення*',
            'poshtomat' => 'Поштомат*',
            'no_call' => 'Не дзвонити для підтвердження замовлення',
            'comment' => 'Додати коментар до замовлення',
            'promo' => 'Є промокод / сертифікат',
            'comment_field' => 'Ваш коментар',
            'promo_field' => 'Промокод або сертифікат',
        ],
        'placeholders' => [
            'city_search' => 'Пошук міста',
            'delivery_methods' => 'Виберіть спосіб доставки',
            'delivery_address' => 'Вулиця, будинок, квартира',
            'branch_search' => 'Пошук відділення',
            'payment_methods' => 'Виберіть спосіб оплати',
            'payment_placeholder' => 'Поки що немає доступних способів оплати',
        ],
        'delivery_methods' => [
            'nova_poshta' => 'Нова Пошта (Відділення)',
            'nova_poshta_courier' => "Нова Пошта (Кур'єр)",
            'nova_poshta_poshtomat' => 'Нова Пошта (Поштомат)',
            'ukr_poshta' => 'Укрпошта',
        ],
        'buttons' => [
            'show_all_items' => 'Показати всі товари (:count)',
            'find_on_map' => 'Знайти на карті',
            'edit_items' => 'Редагувати товари',
            'submit' => 'Оформити замовлення',
        ],
        'warnings' => [
            'choose_city_first' => 'Спочатку виберіть населений пункт',
            'no_delivery_methods' => 'Для вибраного населеного пункту немає доступних способів доставки',
            'no_cities_found' => 'Міста не знайдено',
        ],
        'texts' => [
            'consent' => 'Оформлюючи замовлення, ви підтверджуєте свою згоду з умовами обслуговування та політикою конфіденційності.',
            'subtotal' => 'Загальна сума',
            'delivery' => 'Доставка',
            'total' => 'Підсумок',
        ],
    ],
];
