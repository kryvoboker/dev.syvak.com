<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Користувачі',

    // Labels
    'labels' => [
        'model' => 'Користувач',
        'plural_model' => 'Користувачі',
    ],

    // Helpers
    'helpers' => [
        'email_verified_at' => 'Дата, коли користувач підтвердив свою електронну адресу',
        'password' => 'Пароль має містити принаймні 3 символи, включаючи літери, цифри та спеціальні символи!',
        'is_active' => 'Увімкнути/Вимкнути цього користувача',
    ],

    // Text
    'texts' => [
    ],

    // Error
    'errors' => [
        'cant_delete_special_user' => 'Будь ласка, не намагайтеся видаляти цей спеціальний обліковий запис користувача!',
    ],
];
