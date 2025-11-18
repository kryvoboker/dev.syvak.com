<?php

use App\Providers\PathOverrideServiceProvider;

return [
//    PathOverrideServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AlyoAdminPanelProvider::class,
    Barryvdh\Debugbar\ServiceProvider::class,
];
