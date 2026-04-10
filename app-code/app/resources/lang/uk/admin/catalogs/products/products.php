<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Товари',

    // Labels
    'labels' => [
        'model'                => 'Товар',
        'plural_model'         => 'Товари',
        'variant_model'        => 'Варіація товару',
        'variant_plural_model' => 'Варіації товару',
        'add_discount'         => 'Додати знижку',
        'add_attribute'        => 'Додати атрибут',
        'delete_discount'      => 'Видалити знижку',
        'delete_attribute'     => 'Видалити атрибут',
    ],

    // Pages
    'pages' => [
        'create'         => 'Створити товар',
        'edit'           => 'Редагувати товар',
        'variants'       => 'Варіації товару',
        'create_variant' => 'Створити варіацію товару',
        'edit_variant'   => 'Редагувати варіацію товару',
    ],

    'actions' => [
        'manage_variants' => 'Керувати варіаціями',
        'create_variant'  => 'Створити варіацію',
        'back_to_product' => 'Назад до товару',
    ],

    // Errors
    'errors' => [
        'duplicate_attribute_language' => 'Комбінація атрибуту та мови повинна бути унікальною!',
    ],
];
