<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AlyoAdminPanelProvider;
use App\Providers\ModuleProvidersServiceProvider;
use App\Providers\OpenAiServiceProvider;
use Fruitcake\LaravelDebugbar\ServiceProvider as DebugbarServiceProvider;

return [
    AppServiceProvider::class,
    AlyoAdminPanelProvider::class,
    ModuleProvidersServiceProvider::class,
    OpenAiServiceProvider::class,
    DebugbarServiceProvider::class,
];
