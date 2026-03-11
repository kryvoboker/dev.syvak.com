<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Modules\ModuleCacheService;
use App\Services\Modules\ModuleDefinitionSyncService;
use App\Services\Modules\ModuleDiscoveryService;
use App\Services\Modules\ModuleInstanceService;
use App\Services\Modules\ModuleProviderRegistrarService;
use App\Services\Modules\ModuleProviderResolverService;
use App\Services\Modules\ModuleRuntimeResolverService;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use App\Supports\Services\Images\ImageUrlBuilderService;
use DateTimeInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $new_storage_path = config('filesystems.new_storage_path');

        if ($new_storage_path) {
            config([
                // Override compiled views path
                'view.compiled'                => $new_storage_path . '/framework/views',
                'debugbar.storage.path'        => $new_storage_path . '/debugbar',
                'logging.channels.single.path' => $new_storage_path . '/logs/laravel.log',
                'logging.channels.daily.path'  => $new_storage_path . '/logs/laravel.log',
                'logging.channels.stack.path'  => $new_storage_path . '/logs/laravel.log',
            ]);
        }

        $this->app->singleton(HeaderService::class);
        $this->app->singleton(FooterService::class);
        $this->app->singleton(ImageUrlBuilderService::class);
        $this->app->singleton(AppSettingsService::class);
        $this->app->singleton(ConvertPrice::class);
        $this->app->singleton(ModuleCacheService::class);
        $this->app->singleton(ModuleDiscoveryService::class);
        $this->app->singleton(ModuleDefinitionSyncService::class);
        $this->app->singleton(ModuleInstanceService::class);
        $this->app->singleton(ModuleRuntimeResolverService::class);
        $this->app->singleton(ModuleProviderResolverService::class);
        $this->app->singleton(ModuleProviderRegistrarService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $new_storage_path = config('filesystems.new_storage_path');
        $new_public_path  = config('filesystems.new_public_path');

        if ($new_storage_path && $new_public_path) {
            // Override symbolic links configuration
            config([
                'filesystems.links' => [
                    $new_public_path . '/storage' => $new_storage_path . '/app/public',
                ],
            ]);
        }

        require_once app_path('Supports/helpers.php');

        // Register view namespaces for frontend (catalog) and admin
        // This allows usage like view('catalog::layouts.partials.header')
        $catalog_path = resource_path('views/catalog');

        if (File::isDirectory($catalog_path)) {
            View::addNamespace('catalog', $catalog_path);
        }

        if (app()->isLocal() && app()->hasDebugModeEnabled() === true) {
            // Check SQL queries in the local environment for remote debugging
            DB::listen(function (QueryExecuted $query) {
                $bindings = $query->bindings;

                // Replace placeholders with quoted bindings for readable SQL.
                $sql_template = str_replace('?', '%s', $query->sql);

                $sql = vsprintf($sql_template, array_map(function ($binding) {
                    if (is_string($binding)) {
                        return "'" . addslashes($binding) . "'";
                    }

                    if ($binding instanceof DateTimeInterface) {
                        return "'" . $binding->format('Y-m-d H:i:s') . "'";
                    }

                    if (is_bool($binding)) {
                        return $binding ? '1' : '0';
                    }

                    return $binding === null ? 'NULL' : $binding;
                }, $bindings)) ?: $query->sql;

                $res = $sql;
            });
        }
    }
}
