<?php

return [
    // Navigation
    'navigation_label' => 'Languages',

    // Labels
    'labels'           => [
        'model'        => 'Language',
        'plural_model' => 'Languages',
        'code'         => 'Language Code',
        'name'         => 'Language Name',
    ],

    // Helpers
    'helpers'          => [
        'code'       => 'ISO 639-1 code (e.g., en, uk, ru)',
        'name'       => 'Full language name (e.g., English, Українська)',
        'is_active'  => 'Enable this language for users',
        'is_default' => 'Set as default language for the system',
    ],

    // Text
    'texts'            => [
        'cant_delete_default_language'     => 'Cannot delete default language',
        'cant_delete_last_active_language' => 'Cannot delete last active language',
    ],

    // Error
    'errors'           => [
        'cant_delete_default_language'     => 'Please set another language as default before deleting this one!',
        'cant_delete_last_active_language' => 'At least one active language must remain in the system!',
    ],
];
