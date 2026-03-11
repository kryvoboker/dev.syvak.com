<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Loading Strategies
    |--------------------------------------------------------------------------
    |
    | This list is the source of truth for module provider loading strategies.
    | Module-level values are validated against this list.
    |
    */
    'allowed_strategies' => [
        'eager',
        'route_matched',
        'middleware_after_session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Strategy
    |--------------------------------------------------------------------------
    |
    | Used when module-level strategy is missing or invalid.
    |
    */
    'default_strategy' => 'route_matched',
];
