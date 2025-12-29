<?php

return [
    // Navigation
    'navigation_label' => 'Групи користувачів',

    // Labels
    'labels'           => [
        'model'        => 'Група користувачів',
        'plural_model' => 'Групи користувачів',
    ],

    // Helpers
    'helpers'          => [
        'is_active'  => 'Активувати цю групу користувачів для користувачів',
        'is_default' => 'Встановити як групу за замовчуванням для нових користувачів',
    ],

    // Text
    'texts'            => [
    ],

    // Error
    'errors'           => [
        'cant_delete_default_user_group'     => 'Будь ласка, перед видаленням цієї групи встановіть іншу групу за замовчуванням!',
        'cant_delete_last_active_user_group' => 'У системі має залишитися принаймні одна активна група користувачів!',
    ],
];
