<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Providers;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProductsCarouselServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'ProductsCarousel';

    protected string $nameLower = 'productscarousel';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerCommandSchedules();
            $this->registerTranslations();
            $this->registerConfig();
            $this->registerViews();
            $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

            return;
        }

        if (! $this->shouldBootForCurrentRequest()) {
            return;
        }

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     *
     * This module is runtime-content only, so scaffold route/event
     * providers are intentionally not registered here.
     */
    public function register(): void {}

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $lang_path = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path, $this->nameLower);
            $this->loadJsonTranslationsFrom($lang_path);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $config_path = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($config_path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config_path));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config     = str_replace($config_path . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments   = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments.
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing      = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $view_path   = resource_path('views/modules/' . $this->nameLower);
        $source_path = module_path($this->name, 'resources/views');

        $this->publishes([$source_path => $view_path], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$source_path]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Keep provider boot lazy for web requests:
     * - allow boot in modules admin routes where module settings are managed;
     * - allow boot for storefront requests only when ProductsCarousel is active for current page type.
     */
    private function shouldBootForCurrentRequest(): bool
    {
        if ($this->isModulesAdminRequest()) {
            return $this->isProductsCarouselEnabledGlobally();
        }

        if ($this->isProductsCarouselEnabledGlobally() === false) {
            return false;
        }

        $page_type = function_exists('try_detect_page_type')
            ? try_detect_page_type()
            : null;

        if (blank($page_type)) {
            return false;
        }

        return ModuleDefinition::query()
            ->enabled()
            ->where('nwidart_name', $this->name)
            ->whereHas(
                'instances',
                function (Builder $query) use ($page_type): void {
                    $query
                        ->where('is_enabled', true)
                        ->where(function (Builder $query) use ($page_type): void {
                            $query->whereJsonContains('settings->shared->page_types', $page_type);
                        });
                },
            )
            ->exists();
    }

    private function isProductsCarouselEnabledGlobally(): bool
    {
        return ModuleDefinition::query()
            ->enabled()
            ->where('nwidart_name', $this->name)
            ->exists();
    }

    private function isModulesAdminRequest(): bool
    {
        $route_name = request()->route()?->getName();

        if (is_string($route_name) && Str::startsWith($route_name, 'filament.')) {
            return true;
        }

        $request_path = Request::path();

        return Str::contains($request_path, 'alyo-admin');
    }
}
