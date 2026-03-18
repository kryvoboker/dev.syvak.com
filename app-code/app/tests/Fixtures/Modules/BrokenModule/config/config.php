<?php

declare(strict_types=1);

return [
    'runtime' => [
        'storefront' => [
            'data_service'  => 'Services\\MissingModuleDataService',
            'view'          => 'catalog.pages.home',
            'view_data_key' => 'broken_module_data',
        ],
    ],
];
