<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'User Groups',

    // Labels
    'labels' => [
        'model' => 'User Group',
        'plural_model' => 'User Groups',
    ],

    // Helpers
    'helpers' => [
        'is_active' => 'Enable this user group for users',
        'is_default' => 'Set as default user group for new users',
    ],

    // Text
    'texts' => [
    ],

    // Error
    'errors' => [
        'cant_delete_default_user_group' => 'Please set another user group as default before deleting this one!',
        'cant_delete_last_active_user_group' => 'At least one active user group must remain in the system!',
    ],
];
