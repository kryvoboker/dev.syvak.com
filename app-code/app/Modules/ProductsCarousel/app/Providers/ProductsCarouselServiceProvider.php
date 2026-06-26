<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProductsCarouselServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'ProductsCarousel';

    protected string $name_lower = 'productscarousel';

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

        // Request-level lazy loading is handled by ModuleProvidersServiceProvider + resolver.
        // If this provider was registered for current request, it should fully boot.
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
        $lang_path = resource_path('lang/modules/' . $this->name_lower);

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path, $this->name_lower);
            $this->loadJsonTranslationsFrom($lang_path);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->name_lower);
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
                    $segments   = explode('.', $this->name_lower . '.' . $config_key);

                    // Remove duplicated adjacent segments.
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->name_lower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeModuleConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function mergeModuleConfigFrom(string $path, string $key): void
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
        $view_path   = resource_path('views/modules/' . $this->name_lower);
        $source_path = module_path($this->name, 'resources/views');

        $this->publishes([$source_path => $view_path], ['views', $this->name_lower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$source_path]), $this->name_lower);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->name_lower);
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
            if (is_dir($path . '/modules/' . $this->name_lower)) {
                $paths[] = $path . '/modules/' . $this->name_lower;
            }
        }

        return $paths;
    }
}
