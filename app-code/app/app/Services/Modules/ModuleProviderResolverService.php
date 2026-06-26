<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Resolves module service providers for the current request context.
 *
 * The resolver enforces two rules for every strategy:
 * 1) module is active in database flags;
 * 2) module is relevant for current request/page context.
 */
readonly class ModuleProviderResolverService
{
    public function __construct(
        private ModuleClassResolverService $module_class_resolver_service,
    ) {}

    /**
     * @return list<class-string>
     */
    public function resolveForStrategy(string $strategy, ?Request $request = null): array
    {
        if (app()->runningInConsole() && app()->runningUnitTests() === false) {
            return [];
        }

        $request ??= request();
        $strategy = $this->normalizeStrategy($strategy);

        return $this->resolveProvidersForDefinitions(
            $this->resolveRelevantDefinitions($request),
            $strategy,
        );
    }

    /**
     * @return iterable<ModuleDefinition>
     */
    private function resolveRelevantDefinitions(Request $request): iterable
    {
        if ($this->isModulesAdminRequest($request)) {
            return ModuleDefinition::query()
                ->enabled()
                ->ordered()
                ->get();
        }

        $page_type = try_detect_page_type($request);

        if (blank($page_type)) {
            return [];
        }

        return ModuleDefinition::query()
            ->enabled()
            ->ordered()
            ->whereHas(
                'instances',
                function (Builder $query) use ($page_type): void {
                    $query
                        ->where('is_enabled', true)
                        ->where(function (Builder $query) use ($page_type): void {
                            $query
                                ->whereJsonContains('settings->shared->page_types', $page_type)
                                ->orWhereNull('settings->shared->page_types')
                                ->orWhereJsonLength('settings->shared->page_types', 0);
                        });
                },
            )
            ->get();
    }

    /**
     * @param  iterable<ModuleDefinition>  $definitions
     * @return list<class-string>
     */
    private function resolveProvidersForDefinitions(iterable $definitions, string $strategy): array
    {
        /** @var list<class-string> $providers */
        $providers = collect($definitions)
            ->filter(fn (ModuleDefinition $definition): bool => $this->resolveModuleStrategy($definition) === $strategy)
            ->map(function (ModuleDefinition $definition): ?string {
                $provider_class = $this->module_class_resolver_service->resolve(
                    $definition,
                    sprintf('Providers\\%sServiceProvider', $definition->nwidart_name),
                );

                if ($provider_class === null) {
                    Log::channel('stack')->warning('Module provider class could not be resolved.', [
                        'module_definition_id' => $definition->id,
                        'module_name'          => $definition->nwidart_name,
                    ]);
                }

                return $provider_class;
            })
            ->filter(fn (?string $provider_class): bool => filled($provider_class))
            ->unique()
            ->values()
            ->all();

        return $providers;
    }

    private function resolveModuleStrategy(ModuleDefinition $definition): string
    {
        $module_config_strategy = Arr::get(
            $this->loadModuleConfig($definition),
            'runtime.provider_loading_strategy',
        );

        return $this->normalizeStrategy($module_config_strategy, $definition);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadModuleConfig(ModuleDefinition $definition): array
    {
        $module_path = Str::trim((string) $definition->module_path);

        if ($module_path === '') {
            return [];
        }

        $config_path = $module_path . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        if (! is_file($config_path)) {
            return [];
        }

        $config_data = require $config_path;

        return is_array($config_data) ? $config_data : [];
    }

    private function normalizeStrategy(mixed $strategy, ?ModuleDefinition $definition = null): string
    {
        $allowed_strategies = config('modules-runtime.allowed_strategies', []);

        if (! is_array($allowed_strategies) || $allowed_strategies === []) {
            $allowed_strategies = ['eager', 'route_matched', 'middleware_after_session'];
        }

        $default_strategy = (string) config('modules-runtime.default_strategy', 'route_matched');

        if (! in_array($default_strategy, $allowed_strategies, true)) {
            $default_strategy = (string) array_first($allowed_strategies);
        }

        if (is_string($strategy) && in_array($strategy, $allowed_strategies, true)) {
            return $strategy;
        }

        if (filled($strategy)) {
            Log::channel('stack')->warning('Invalid module provider loading strategy. Fallback to default strategy.', [
                'module_definition_id' => $definition?->id,
                'module_name'          => $definition?->nwidart_name,
                'provided_strategy'    => $strategy,
                'default_strategy'     => $default_strategy,
            ]);
        }

        return $default_strategy;
    }

    private function isModulesAdminRequest(Request $request): bool
    {
        $route_name = $request->route()?->getName();

        if (is_string($route_name) && Str::startsWith($route_name, 'filament.')) {
            return true;
        }

        return Str::contains($request->path(), 'alyo-admin');
    }
}
