<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AlyoAdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\ModuleProvidersServiceProvider::class,
    App\Providers\OpenAiServiceProvider::class,
    Fruitcake\LaravelDebugbar\ServiceProvider::class,
];
