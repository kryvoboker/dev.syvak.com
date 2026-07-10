<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Nova Poshta',
    'title'            => 'Nova Poshta Sync',
    'description'      => 'Refresh regions, cities, post offices, and poshtomats from the Nova Poshta API.',
    'sections'         => [
        'shared'  => [
            'title'       => 'Shared settings',
            'description' => 'Set the current Nova Poshta API key and delivery cost options. The values are stored in the application global configs.',
        ],
        'sync'    => [
            'title'       => 'Dictionary refresh',
            'description' => 'Running a full Nova Poshta sync rebuilds the regions, cities, post offices, and poshtomats tables.',
            'idle'        => 'Sync is not running.',
        ],
        'summary' => [
            'title' => 'Current state',
            'empty' => 'The sync has not been run yet.',
        ],
    ],
    'labels'           => [
        'page_types'               => 'Show on pages',
        'api_key'                  => 'API key',
        'delivery_cost'            => 'Delivery cost',
        'is_delivery_cost_enabled' => 'Count delivery cost?',
    ],
    'actions'          => [
        'sync'              => 'Run update',
        'stop_sync'         => 'Stop sync',
        'sync_confirmation' => 'This will fully refresh the local Nova Poshta tables. Continue?',
        'save_settings'     => 'Save settings',
    ],
    'sync'             => [
        'labels'   => [
            'status'           => 'Status',
            'overall_progress' => 'Overall progress',
            'stage'            => 'Stage',
            'phase'            => 'Phase',
            'page'             => 'Page',
            'processed_rows'   => 'Processed rows',
            'stage_progress'   => 'Stage progress',
        ],
        'states'   => [
            'idle'      => 'Waiting to start',
            'running'   => 'Running',
            'completed' => 'Completed',
            'failed'    => 'Failed',
            'stopped'   => 'Stopped',
        ],
        'phases'   => [
            'collect'  => 'Collecting data',
            'finalize' => 'Confirming rebuild',
        ],
        'stages'   => [
            'regions'      => 'Regions',
            'cities'       => 'Cities',
            'post_offices' => 'Post offices',
            'poshtomats'   => 'Poshtomats',
        ],
        'messages' => [
            'started'                => 'Sync has started.',
            'stop_requested'         => 'The sync stop request was accepted. The current batch will finish first.',
            'stopped'                => 'Sync stopped.',
            'regions_completed'      => 'Regions updated. Moving to cities.',
            'cities_completed'       => 'Cities updated. Moving to post offices.',
            'post_offices_completed' => 'Post offices updated. Moving to poshtomats.',
            'poshtomats_completed'   => 'Poshtomats updated. Moving to finalization.',
            'completed'              => 'Sync completed.',
            'next_stage'             => 'Preparing stage: :stage.',
            'ready_to_finalize'      => 'Stage :stage has been collected. Confirming table rebuild.',
            'collecting_page'        => 'Loading :stage, page :current of :total.',
        ],
    ],
    'stats'            => [
        'regions'      => 'Regions',
        'cities'       => 'Cities',
        'post_offices' => 'Post offices',
        'poshtomats'   => 'Poshtomats',
    ],
    'notifications'    => [
        'sync_started'     => 'Nova Poshta sync has started. The page will refresh automatically.',
        'sync_completed'   => 'Nova Poshta data was refreshed successfully.',
        'sync_stopped'     => 'Nova Poshta sync was stopped.',
        'sync_failed'      => 'Failed to refresh Nova Poshta data.',
        'sync_not_running' => 'The sync is not running right now.',
        'settings_saved'   => 'Nova Poshta settings saved.',
    ],
    'helpers'          => [
        'api_key'                  => 'The Nova Poshta key is stored in the application global configs and is used to synchronize data from the API.',
        'delivery_cost'            => 'This value is used as the Nova Poshta delivery cost in the module.',
        'is_delivery_cost_enabled' => 'When disabled, the module will ignore the saved delivery cost.',
    ],
    'validation'       => [
        'api_key_required'       => 'The Nova Poshta API key must be set in the global configs.',
        'delivery_cost_required' => 'The Nova Poshta delivery cost must be set.',
    ],
];
