<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Order statuses',

    'labels' => [
        'model' => 'Order status',
        'plural_model' => 'Order statuses',
        'name' => 'Status name',
    ],

    'helpers' => [
        'code' => 'Unique internal code used by the order lifecycle.',
        'is_default' => 'The default status is used for newly created orders. Another status must be made default before this one can be changed.',
        'is_active' => 'Inactive statuses cannot be selected for new orders.',
    ],

    'columns' => [
        'name' => 'Names',
    ],

    'actions' => [
        'activate' => 'Enable selected',
        'deactivate' => 'Disable selected',
    ],

    'notifications' => [
        'activated' => 'Selected order statuses have been enabled.',
        'deactivated' => 'Selected order statuses have been disabled.',
    ],

    'errors' => [
        'default_required' => 'There must always be one default and active order status.',
        'default_must_be_active' => 'The default order status must always be active.',
        'cannot_unset_default' => 'Set another order status as default before changing this status.',
        'cannot_delete_default' => 'The default order status cannot be deleted.',
        'operation_failed' => 'The order status operation could not be completed.',
    ],
];
