<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AlyoAdminPanelProvider::class,
    App\Providers\OpenAiServiceProvider::class,
    Barryvdh\Debugbar\ServiceProvider::class,
    App\Providers\ModuleProvidersServiceProvider::class,
];
