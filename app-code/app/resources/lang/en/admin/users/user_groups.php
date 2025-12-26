<?php

return [
    // Navigation
    'navigation_label' => 'User Groups',

    // Labels
    'labels'           => [
        'model'        => 'User Group',
        'plural_model' => 'User Groups',
    ],

    // Helpers
    'helpers'          => [
        'is_active'  => 'Enable this user group for users',
        'is_default' => 'Set as default user group for new users',
    ],

    // Text
    'texts'            => [
        'cant_delete_default_user_group'     => 'Cannot delete default user group',
        'cant_delete_last_active_user_group' => 'Cannot delete last active user group',
    ],

    // Error
    'errors'           => [
        'cant_delete_default_user_group'     => 'Please set another user group as default before deleting this one!',
        'cant_delete_last_active_user_group' => 'At least one active user group must remain in the system!',
    ],
];
