<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Users',

    // Labels
    'labels' => [
        'model' => 'User',
        'plural_model' => 'Users',
        'role' => 'Filament Shield role',
    ],

    // Helpers
    'helpers' => [
        'email_verified_at' => 'The date when the user verified their email address',
        'password' => 'The password must contain at least 3 characters, including letters, numbers, and special symbols!',
        'is_active' => 'Enable/Disable this user',
        'role' => 'Select a role for admin panel access. Leave empty for a regular user without admin access.',
    ],

    // Options
    'options' => [
        'no_role' => 'Regular user without admin access',
    ],

    // Text
    'texts' => [
    ],

    // Error
    'errors' => [
        'cant_delete_special_user' => 'Please do not attempt to delete this special user account!',
    ],
];
