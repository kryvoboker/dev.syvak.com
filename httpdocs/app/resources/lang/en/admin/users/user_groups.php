<?php

return [
    // Navigation
    'navigation_label'                         => 'User Groups',

    // Labels
    'label_model'                              => 'User Group',
    'label_plural_model'                       => 'User Groups',
    'label_name'                               => 'Name',
    'label_description'                        => 'Description',
    'label_is_active'                          => 'Is Active',
    'label_is_default'                         => 'Is Default Group',

    // Helpers
    'helper_is_active'                         => 'Enable/Disable this user group',
    'helper_is_default'                        => 'Set as default user group for new users',

    // Columns
    'column_name'                              => 'Name',
    'column_description'                       => 'Description',
    'column_active'                            => 'Active',
    'column_default'                           => 'Default',
    'column_created_at'                        => 'Created At',

    // Text
    'text_cant_delete_default_user_group'      => 'Cannot delete default user group',
    'text_cant_delete_last_active_user_group'  => 'Cannot delete last active user group',

    // Error
    'error_cant_delete_default_user_group'     => 'Please set another user group as default before deleting this one!',
    'error_cant_delete_last_active_user_group' => 'At least one active user group must remain in the system!',
];
