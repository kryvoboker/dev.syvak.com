<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Nova Poshta',
    'title' => 'Nova Poshta Sync',
    'description' => 'Refresh regions, cities, post offices, and poshtomats from the Nova Poshta API.',
    'sections' => [
        'shared' => 'Shared settings',
        'sync' => [
            'title' => 'Dictionary refresh',
            'description' => 'Running a full Nova Poshta sync rebuilds the regions, cities, post offices, and poshtomats tables.',
        ],
        'summary' => [
            'title' => 'Current state',
            'empty' => 'The sync has not been run yet.',
        ],
    ],
    'labels' => [
        'page_types' => 'Show on pages',
        'api_key' => 'API key',
    ],
    'actions' => [
        'sync' => 'Run update',
        'sync_confirmation' => 'This will fully refresh the local Nova Poshta tables. Continue?',
    ],
    'stats' => [
        'regions' => 'Regions',
        'cities' => 'Cities',
        'post_offices' => 'Post offices',
        'poshtomats' => 'Poshtomats',
    ],
    'notifications' => [
        'sync_completed' => 'Nova Poshta data was refreshed successfully.',
        'sync_failed' => 'Failed to refresh Nova Poshta data.',
    ],
    'helpers' => [
        'api_key' => 'The Nova Poshta key is used to synchronize data from the API.',
    ],
];
