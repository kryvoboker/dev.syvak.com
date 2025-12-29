<?php

return [
    // Navigation
    'navigation_label' => 'Users',

    // Labels
    'labels'           => [
        'model'        => 'User',
        'plural_model' => 'Users',
    ],

    // Helpers
    'helpers'          => [
        'email_verified_at' => 'The date when the user verified their email address',
        'password'          => 'The password must contain at least 3 characters, including letters, numbers, and special symbols!',
        'is_active'         => 'Enable/Disable this user',
    ],

    // Text
    'texts'            => [
    ],

    // Error
    'errors'           => [
        'cant_delete_special_user' => 'Please do not attempt to delete this special user account!',
    ],
];
