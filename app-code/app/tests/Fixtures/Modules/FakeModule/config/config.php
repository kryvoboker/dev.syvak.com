<?php

declare(strict_types=1);

return [
    'runtime' => [
        'storefront' => [
            'data_service'  => 'Services\\FakeModuleModuleDataService',
            'view'          => 'catalog.pages.home',
            'view_data_key' => 'fake_module_data',
        ],
    ],
];
