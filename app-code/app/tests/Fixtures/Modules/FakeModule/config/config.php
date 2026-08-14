<?php

declare(strict_types=1);

return [
    'runtime' => [
        'storefront' => [
            'data_service' => 'Services\\Storefront\\FakeModuleStorefrontService',
            'view' => 'storefront.pages.home',
            'view_data_key' => 'fake_module_data',
        ],
    ],
];
