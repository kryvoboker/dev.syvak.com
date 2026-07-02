<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Ukr Poshta',
    'title' => 'Ukr Poshta Sync',
    'description' => 'Refresh regions, districts, cities, and post offices from the Ukr Poshta API.',
    'sections' => [
        'shared' => [
            'title' => 'Shared settings',
            'description' => 'Set the current Ukr Poshta API key and delivery cost options. The values are stored in the application global configs.',
        ],
        'sync' => [
            'title' => 'Dictionary refresh',
            'description' => 'Running a full Ukr Poshta sync rebuilds the regions, districts, cities, and post offices tables.',
            'idle' => 'Sync is not running.',
        ],
        'summary' => [
            'title' => 'Current state',
            'empty' => 'The sync has not been run yet.',
        ],
    ],
    'labels' => [
        'api_key' => 'API key',
        'delivery_cost' => 'Delivery cost',
        'is_delivery_cost_enabled' => 'Count delivery cost?',
    ],
    'actions' => [
        'sync' => 'Run update',
        'stop_sync' => 'Stop sync',
        'sync_confirmation' => 'This will fully refresh the local Ukr Poshta tables. Continue?',
        'save_settings' => 'Save settings',
    ],
    'sync' => [
        'labels' => [
            'status' => 'Status',
            'overall_progress' => 'Overall progress',
            'stage' => 'Stage',
            'phase' => 'Phase',
            'processed_rows' => 'Processed items',
            'stage_progress' => 'Stage progress',
        ],
        'states' => [
            'idle' => 'Waiting to start',
            'running' => 'Running',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'stopped' => 'Stopped',
        ],
        'phases' => [
            'collect' => 'Collecting data',
            'finalize' => 'Confirming rebuild',
        ],
        'stages' => [
            'regions' => 'Regions',
            'districts' => 'Districts',
            'cities' => 'Cities',
            'post_offices' => 'Post offices',
        ],
        'messages' => [
            'started' => 'Sync has started.',
            'stop_requested' => 'The sync stop request was accepted. The current batch will finish first.',
            'stopped' => 'Sync stopped.',
            'regions_completed' => 'Regions updated. Moving to districts.',
            'districts_completed' => 'Districts updated. Moving to cities.',
            'cities_completed' => 'Cities updated. Moving to post offices.',
            'completed' => 'Sync completed.',
            'collecting_item' => 'Loading :stage item :current of :total.',
        ],
    ],
    'stats' => [
        'regions' => 'Regions',
        'districts' => 'Districts',
        'cities' => 'Cities',
        'post_offices' => 'Post offices',
    ],
    'notifications' => [
        'sync_started' => 'Ukr Poshta sync has started. The page will refresh automatically.',
        'sync_completed' => 'Ukr Poshta data was refreshed successfully.',
        'sync_stopped' => 'Ukr Poshta sync was stopped.',
        'stop_requested' => 'The Ukr Poshta sync stop request was accepted.',
        'sync_failed' => 'Failed to refresh Ukr Poshta data.',
        'sync_not_running' => 'The sync is not running right now.',
        'settings_saved' => 'Ukr Poshta settings saved.',
    ],
    'helpers' => [
        'api_key' => 'The Ukr Poshta key is stored in the application global configs and is used to synchronize data from the API.',
        'delivery_cost' => 'This value is used as the Ukr Poshta delivery cost in the module.',
        'is_delivery_cost_enabled' => 'When disabled, the module will ignore the saved delivery cost.',
    ],
    'validation' => [
        'api_key_required' => 'The Ukr Poshta API key must be set in the global configs.',
        'delivery_cost_required' => 'The Ukr Poshta delivery cost must be set.',
    ],
];
