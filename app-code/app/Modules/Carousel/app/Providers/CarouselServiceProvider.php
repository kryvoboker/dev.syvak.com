<?php

declare(strict_types=1);

namespace Modules\Carousel\Providers;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CarouselServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Carousel';

    protected string $nameLower = 'carousel';

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
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
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
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config     = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments   = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments
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
        $viewPath   = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

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
     * We keep module boot lazy for web requests:
     * - allow boot in admin panel where module settings are edited;
     * - allow boot for storefront requests only when Carousel is active for page type;
     * - skip all heavy setup when module is not used on current request.
     */
    private function shouldBootForCurrentRequest(): bool
    {
        if ($this->isModulesAdminRequest()) {
            return $this->isCarouselEnabledGlobally();
        }

        if ($this->isCarouselEnabledGlobally() === false) {
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
                            $query
                                ->whereJsonContains('settings->shared->page_types', $page_type);
                        });
                },
            )
            ->exists();
    }

    private function isCarouselEnabledGlobally(): bool
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
