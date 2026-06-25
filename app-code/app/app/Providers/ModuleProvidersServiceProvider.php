<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Modules\ModuleProviderRegistrarService;
use App\Services\Modules\ModuleProviderResolverService;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Registers module providers after all application providers are fully booted.
 *
 * This provider is responsible for deterministic ordering and lazy loading.
 */
class ModuleProvidersServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap module providers after application providers are fully booted.
     *
     * This guarantees deterministic order:
     * 1) providers from bootstrap/providers.php
     * 2) active module providers needed for current request
     *
     * @throws Throwable
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        // Eager strategy is resolved during provider bootstrap and can use request path fallback.
        app(ModuleProviderRegistrarService::class)->register(
            app(ModuleProviderResolverService::class)->resolveForStrategy('eager', request()),
        );

        // Route-matched strategy waits until route is resolved and request->route() is available.
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            app(ModuleProviderRegistrarService::class)->register(
                app(ModuleProviderResolverService::class)->resolveForStrategy('route_matched', $event->request),
            );
        });
    }
}
