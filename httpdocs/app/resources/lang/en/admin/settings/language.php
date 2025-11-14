<?php

return [
    // Navigation
    'navigation_label'                       => 'Languages',

    // Labels
    'label_model'                            => 'Language',
    'label_plural_model'                     => 'Languages',
    'label_code'                             => 'Language Code',
    'label_name'                             => 'Language Name',
    'label_is_active'                        => 'Is Active',
    'label_is_default'                       => 'Is Default Language',

    // Helpers
    'helper_code'                            => 'ISO 639-1 code (e.g., en, uk, ru)',
    'helper_name'                            => 'Full language name (e.g., English, Українська)',
    'helper_is_active'                       => 'Enable this language for users',
    'helper_is_default'                      => 'Set as default language for the system',

    // Columns
    'column_code'                            => 'Code',
    'column_name'                            => 'Name',
    'column_active'                          => 'Active',
    'column_default'                         => 'Default',
    'column_created_at'                      => 'Created',

    // Filters
    'filter_active'                          => 'Active Languages',
    'filter_default'                         => 'Default Language',
    'placeholder_all_languages'              => 'All Languages',
    'true_label_active_only'                 => 'Active only',
    'false_label_inactive_only'              => 'Inactive only',
    'true_label_default_only'                => 'Default only',

    // Text
    'text_cant_delete_default_language'      => 'Cannot delete default language',
    'text_cant_delete_last_active_language'  => 'Cannot delete last active language',

    // Error
    'error_cant_delete_default_language'     => 'Please set another language as default before deleting this one!',
    'error_cant_delete_last_active_language' => 'At least one active language must remain in the system!',
];
