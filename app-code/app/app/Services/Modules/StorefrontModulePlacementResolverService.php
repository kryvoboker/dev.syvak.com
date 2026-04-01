<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Resolves storefront modules for a concrete page placement and converts them
 * into render-ready include descriptors.
 */
class StorefrontModulePlacementResolverService
{
    /**
     * @var array<string, array<int, array{
     *     module_definition_id: int,
     *     module_name: string,
     *     view: string,
     *     view_data: array<string, mixed>
     * }>>
     */
    private array $resolved_placements_cache = [];

    public function __construct(
        private readonly ModuleClassResolverService $module_class_resolver_service,
    ) {}

    /**
     * @return array<int, array{
     *     module_definition_id: int,
     *     module_name: string,
     *     view: string,
     *     view_data: array<string, mixed>
     * }>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        $cache_key = $placement . '|' . ($page_type ?? 'null');

        if (array_key_exists($cache_key, $this->resolved_placements_cache)) {
            return $this->resolved_placements_cache[$cache_key];
        }

        try {
            /** @var Collection<int, ModuleDefinition> $definitions */
            $definitions = resolve_modules_for_context($placement);
        } catch (BindingResolutionException|CircularDependencyException $e) {
            report($e);
        }

        $resolved_items = $definitions
            ->map(fn(ModuleDefinition $definition): array => $this->resolveDefinitionEntries($definition, $placement, $page_type))
            ->collapse()
            ->values()
            ->all();

        $this->resolved_placements_cache[$cache_key] = $resolved_items;

        return $resolved_items;
    }

    /**
     * @return array<int, array{
     *     module_definition_id: int,
     *     module_name: string,
     *     view: string,
     *     view_data: array<string, mixed>
     * }>
     */
    private function resolveDefinitionEntries(ModuleDefinition $definition, string $placement, ?string $page_type): array
    {
        $module_config = $this->loadModuleConfig($definition);

        $data_service_class = $this->resolveDataServiceClass($definition, $module_config);

        if ($data_service_class === null) {
            Log::channel('stack')->warning('Storefront module data service could not be resolved.', [
                'module_definition_id' => $definition->id,
                'module_name'          => $definition->nwidart_name,
            ]);

            return [];
        }

        $view = Str::trim((string)Arr::get($module_config, 'runtime.storefront.view', ''));

        if (blank($view) || View::exists($view) === false) {
            Log::channel('stack')->warning('Storefront module view is missing or invalid.', [
                'module_definition_id' => $definition->id,
                'module_name'          => $definition->nwidart_name,
                'view'                 => $view,
            ]);

            return [];
        }

        /** @var object $data_service */
        $data_service = app($data_service_class);

        if (method_exists($data_service, 'resolveForPlacement') === false) {
            Log::channel('stack')->warning('Storefront module data service has no resolveForPlacement method.', [
                'module_definition_id' => $definition->id,
                'module_name'          => $definition->nwidart_name,
                'data_service_class'   => $data_service_class,
            ]);

            return [];
        }

        /** @var array<int, array<string, mixed>> $module_items */
        $module_items = $data_service->resolveForPlacement($placement, $page_type);

        $view_data_key = Str::trim((string)Arr::get($module_config, 'runtime.storefront.view_data_key', 'module_data'));

        return collect($module_items)
            ->map(function (array $item) use ($definition, $view, $view_data_key, $page_type): array {
                return [
                    'module_definition_id' => (int)$definition->id,
                    'module_name'          => (string)$definition->nwidart_name,
                    'view'                 => $view,
                    'view_data'            => [
                        $view_data_key => $item,
                        'page_type'    => $page_type,
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $module_config
     */
    private function resolveDataServiceClass(ModuleDefinition $definition, array $module_config): ?string
    {
        $service_relative_class = Arr::get($module_config, 'runtime.storefront.data_service');

        if (!is_string($service_relative_class) || blank($service_relative_class)) {
            $service_relative_class = sprintf('Services\\%sModuleDataService', $definition->nwidart_name);
        }

        return $this->module_class_resolver_service->resolve($definition, $service_relative_class);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadModuleConfig(ModuleDefinition $definition): array
    {
        $module_path = Str::trim((string)$definition->module_path);

        if ($module_path === '') {
            return [];
        }

        $config_path = $module_path . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        if (is_file($config_path) === false) {
            return [];
        }

        $config_data = require $config_path;

        return is_array($config_data) ? $config_data : [];
    }
}
