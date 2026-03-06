<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Modules',
    'labels'           => [
        'model'                    => 'Module',
        'plural_model'             => 'Modules',
        'name'                     => 'Name',
        'slug'                     => 'Slug',
        'nwidart_name'             => 'Code Module',
        'module_path'              => 'Path',
        'description'              => 'Description',
        'is_installed'             => 'Installed',
        'is_enabled'               => 'Globally Enabled',
        'is_enabled_in_filesystem' => 'Filesystem Enabled',
        'sort_order'               => 'Sort Order',
        'settings_schema'          => 'Settings Schema',
        'meta'                     => 'Meta',
    ],
    'columns' => [
        'name'                     => 'Name',
        'slug'                     => 'Slug',
        'nwidart_name'             => 'Code Module',
        'instances_count'          => 'Instances',
        'is_enabled'               => 'Global',
        'is_installed'             => 'Installed',
        'is_enabled_in_filesystem' => 'Filesystem',
        'module_path'              => 'Path',
    ],
    'filters' => [
        'name'                     => 'Module',
        'is_enabled'               => 'Global State',
        'enabled_only'             => 'Enabled Only',
        'disabled_only'            => 'Disabled Only',
        'is_installed'             => 'Installation State',
        'installed_only'           => 'Installed Only',
        'not_installed_only'       => 'Not Installed Only',
        'is_enabled_in_filesystem' => 'Filesystem State',
        'filesystem_enabled_only'  => 'Filesystem Enabled Only',
        'filesystem_disabled_only' => 'Filesystem Disabled Only',
    ],
    'sections' => [
        'definition'      => 'Definition',
        'state'           => 'State',
        'settings_schema' => 'Settings Schema',
        'meta'            => 'Meta',
    ],
    'actions' => [
        'sync'         => 'Sync Modules',
        'enable'       => 'Enable',
        'disable'      => 'Disable',
        'add_instance' => 'Add Instance',
    ],
    'notifications' => [
        'enabled'  => 'Module enabled globally.',
        'disabled' => 'Module disabled globally.',
        'synced'   => 'Module sync completed. Created: :created, updated: :updated, missing: :missing.',
    ],
];
