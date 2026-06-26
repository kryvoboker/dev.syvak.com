<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleDefinitionSyncService
{
    public function __construct(
        private readonly ModuleDiscoveryService $module_discovery_service,
        private readonly ModuleCacheService $module_cache_service,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function sync(): array
    {
        Log::channel('daily')->info('Module definitions sync started.');

        try {
            $summary = DB::transaction(function (): array {
                $discovered_modules = $this->module_discovery_service
                    ->discover()
                    ->keyBy('nwidart_name');

                /** @var Collection<string, ModuleDefinition> $existing_definitions */
                $existing_definitions = ModuleDefinition::query()->get()->keyBy('nwidart_name');

                $created_count = 0;
                $updated_count = 0;

                foreach ($discovered_modules as $nwidart_name => $module_data) {
                    $definition = $existing_definitions->get($nwidart_name);

                    if ($definition === null) {
                        ModuleDefinition::query()->create([
                            ...$module_data,
                            'is_enabled' => (bool) $module_data['is_enabled_in_filesystem'],
                        ]);

                        $created_count++;

                        Log::channel('daily')->info('Module definition created during sync.', [
                            'nwidart_name' => $nwidart_name,
                            'slug' => $module_data['slug'],
                        ]);

                        continue;
                    }

                    $definition->fill([
                        'name' => $module_data['name'],
                        'slug' => $module_data['slug'],
                        'module_path' => $module_data['module_path'],
                        'description' => $module_data['description'],
                        'is_installed' => true,
                        'is_enabled_in_filesystem' => (bool) $module_data['is_enabled_in_filesystem'],
                        'settings_schema' => $this->mergeDefinitionArray($definition->settings_schema, $module_data['settings_schema']),
                        'meta' => $this->mergeDefinitionArray($definition->meta, $module_data['meta']),
                    ]);

                    if ($definition->isDirty()) {
                        $definition->save();
                        $updated_count++;

                        Log::channel('daily')->info('Module definition updated during sync.', [
                            'definition_id' => $definition->id,
                            'nwidart_name' => $definition->nwidart_name,
                        ]);
                    }
                }

                $missing_count = 0;

                foreach ($existing_definitions as $definition) {
                    if ($discovered_modules->has($definition->nwidart_name)) {
                        continue;
                    }

                    $definition->fill([
                        'is_installed' => false,
                        'is_enabled_in_filesystem' => false,
                    ]);

                    if ($definition->isDirty()) {
                        $definition->save();
                        $missing_count++;

                        Log::channel('stack')->warning('Module definition points to a missing filesystem module.', [
                            'definition_id' => $definition->id,
                            'nwidart_name' => $definition->nwidart_name,
                            'slug' => $definition->slug,
                        ]);
                    }
                }

                return [
                    'created' => $created_count,
                    'updated' => $updated_count,
                    'missing' => $missing_count,
                ];
            });

            $this->module_cache_service->flush();

            Log::channel('daily')->info('Module definitions sync finished.', $summary);

            return $summary;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Module definitions sync failed.', [
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * @return array<mixed>
     */
    private function mergeDefinitionArray(mixed $current_value, mixed $discovered_value): array
    {
        return array_replace_recursive(
            is_array($current_value) ? $current_value : [],
            is_array($discovered_value) ? $discovered_value : [],
        );
    }
}
