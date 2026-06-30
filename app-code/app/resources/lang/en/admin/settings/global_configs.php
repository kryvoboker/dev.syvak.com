<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Global Configs',

    // Labels
    'labels' => [
        'global_configs' => 'Global Configs',
        'selected' => 'Selected',
        'key' => 'Key',
        'value' => 'Value',
        'is_active' => 'Is Active',
    ],

    // Sections
    'sections' => [
        'global_configs' => 'Global Configs',
    ],

    // Helpers
    'helpers' => [
        'global_configs' => 'Empty rows are ignored on save. The value can contain plain text, numbers, JSON, serialized data, or null.',
    ],

    // Placeholders
    'placeholders' => [
        'key' => 'site.header.title',
        'value' => 'Any string value or JSON payload',
        'search' => 'Search by key or value',
    ],

    // Actions
    'actions' => [
        'create' => 'Create config',
        'add' => 'Add config',
        'delete_all' => 'Delete all configs',
        'disable_selected' => 'Disable selected configs',
        'delete_selected' => 'Delete selected configs',
    ],

    // Filters
    'filters' => [
        'status' => 'Status',
        'key' => 'Name',
        'value' => 'Value',
    ],

    // Notifications
    'notifications' => [
        'saved_single' => 'Global config was saved.',
        'saved' => 'Global configs were saved. Created: :created_count, updated: :updated_count, deleted: :deleted_count.',
        'deleted_all' => 'Deleted global configs: :deleted_count.',
        'disabled_selected' => 'Disabled selected configs: :updated_count.',
        'deleted_selected' => 'Deleted selected configs: :deleted_count.',
    ],
];
