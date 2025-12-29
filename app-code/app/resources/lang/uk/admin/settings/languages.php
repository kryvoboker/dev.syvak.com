<?php

return [
    // Navigation
    'navigation_label' => 'Мови',

    // Labels
    'labels'           => [
        'model'        => 'Мова',
        'plural_model' => 'Мови',
        'code'         => 'Код мови',
        'name'         => 'Назва мови',
    ],

    // Helpers
    'helpers'          => [
        'code'       => 'Код ISO 639-1 (наприклад: en, uk, ru)',
        'name'       => 'Повна назва мови (наприклад: Англійська, Українська)',
        'is_active'  => 'Увімкнути цю мову для користувачів',
        'is_default' => 'Встановити як мову за замовчуванням для системи',
    ],

    // Text
    'texts'            => [
        'cant_delete_default_language'     => 'Не можна видалити мову за замовчуванням',
        'cant_delete_last_active_language' => 'Не можна видалити останню активну мову',
    ],

    // Error
    'errors'           => [
        'cant_delete_default_language'     => 'Будь ласка, встановіть іншу мову за замовчуванням перед видаленням цієї!',
        'cant_delete_last_active_language' => 'У системі має лишатися принаймні одна активна мова!',
    ],
];
