<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AlyoAdminPanelProvider;
use App\Providers\ModuleProvidersServiceProvider;
use App\Providers\OpenAiServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Fruitcake\LaravelDebugbar\ServiceProvider;

return [
    AppServiceProvider::class,
    AlyoAdminPanelProvider::class,
    ModuleProvidersServiceProvider::class,
    OpenAiServiceProvider::class,
    TelescopeServiceProvider::class,
    ServiceProvider::class,
];
