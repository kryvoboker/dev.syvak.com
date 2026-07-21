<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Payment statuses',

    'labels' => [
        'model' => 'Payment status',
        'plural_model' => 'Payment statuses',
        'name' => 'Status name',
    ],

    'helpers' => [
        'code' => 'Unique internal code used by the payment lifecycle.',
        'is_default' => 'The default status is used for newly created payments. Another status must be made default before this one can be changed.',
        'is_active' => 'Inactive statuses cannot be selected for new payments.',
    ],

    'columns' => [
        'name' => 'Names',
    ],

    'actions' => [
        'activate' => 'Enable selected',
        'deactivate' => 'Disable selected',
    ],

    'notifications' => [
        'activated' => 'Selected payment statuses have been enabled.',
        'deactivated' => 'Selected payment statuses have been disabled.',
    ],

    'errors' => [
        'default_required' => 'There must always be one default and active payment status.',
        'default_must_be_active' => 'The default payment status must always be active.',
        'cannot_unset_default' => 'Set another payment status as default before changing this status.',
        'cannot_delete_default' => 'The default payment status cannot be deleted.',
        'operation_failed' => 'The payment status operation could not be completed.',
    ],
];
