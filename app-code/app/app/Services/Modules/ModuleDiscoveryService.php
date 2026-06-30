<?php

declare(strict_types=1);

namespace App\Services\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Module as NwidartModule;
use Throwable;

class ModuleDiscoveryService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function discover(): Collection
    {
        Log::channel('daily')->info('Module discovery started.');

        try {
            /** @var array<string, NwidartModule> $modules */
            $modules = Module::all();

            $discovered_modules = collect($modules)
                ->map(function (NwidartModule $module): array {
                    return [
                        'name' => $module->getStudlyName(),
                        'slug' => $module->getKebabName(),
                        'nwidart_name' => $module->getName(),
                        'module_path' => $module->getPath(),
                        'description' => $module->getDescription(),
                        'is_installed' => true,
                        'is_enabled_in_filesystem' => $module->isEnabled(),
                        'settings_schema' => [],
                        'meta' => [
                            'priority' => $module->getPriority(),
                            'keywords' => $module->get('keywords', []),
                            'aliases' => $module->get('aliases', []),
                            'files' => $module->get('files', []),
                            'providers' => $module->get('providers', []),
                            'requires' => $module->get('requires', []),
                            'composer_name' => $module->getComposerAttr('name'),
                            'admin' => $module->get('admin', []),
                        ],
                    ];
                })
                ->values();

            /** @var Collection<int, array<string, mixed>> $discovered_modules */
            Log::channel('daily')->info('Module discovery finished.', [
                'modules_count' => $discovered_modules->count(),
            ]);

            return $discovered_modules;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Module discovery failed.', [
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
