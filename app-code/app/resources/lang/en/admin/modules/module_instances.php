<?php

declare(strict_types=1);

return [
    'labels' => [
        'name'          => 'Name',
        'slug'          => 'Slug',
        'placement'     => 'Placement',
        'context_key'   => 'Context Key',
        'is_enabled'    => 'Enabled',
        'sort_order'    => 'Sort Order',
        'settings'      => 'Settings',
        'meta'          => 'Meta',
        'setting_key'   => 'Key',
        'setting_value' => 'Value',
    ],
    'columns' => [
        'name'        => 'Name',
        'slug'        => 'Slug',
        'placement'   => 'Placement',
        'context_key' => 'Context Key',
        'is_enabled'  => 'Enabled',
        'sort_order'  => 'Sort Order',
    ],
    'filters' => [
        'is_enabled'    => 'State',
        'enabled_only'  => 'Enabled Only',
        'disabled_only' => 'Disabled Only',
    ],
    'actions' => [
        'add'       => 'Add',
        'duplicate' => 'Duplicate',
    ],
    'placeholders' => [
        'placement'   => 'home.hero',
        'context_key' => 'product.card',
    ],
    'notifications' => [
        'created'    => 'Module instance created.',
        'duplicated' => 'Module instance duplicated.',
    ],
];
