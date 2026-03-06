<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ModuleRuntimeResolverService
{
    public function __construct(
        private readonly ModuleCacheService $module_cache_service,
    ) {}

    /**
     * @return Collection<int, ModuleDefinition>
     */
    public function resolve(?string $placement = null, ?string $context_key = null): Collection
    {
        $cache_key = 'runtime:' . ($placement ?? 'all') . ':' . ($context_key ?? 'all');

        /** @var Collection<int, ModuleDefinition> $resolved_modules */
        $resolved_modules = $this->module_cache_service->remember(
            $cache_key,
            function () use ($placement, $context_key): Collection {
                Log::channel('daily')->info('Resolving module runtime payload.', [
                    'placement'   => $placement,
                    'context_key' => $context_key,
                ]);

                $definitions = ModuleDefinition::query()
                    ->enabled()
                    ->ordered()
                    ->with([
                        'instances' => function ($query) use ($placement, $context_key): void {
                            $query
                                ->where('is_enabled', true)
                                ->when(filled($placement), fn ($builder) => $builder->where('placement', $placement))
                                ->when(filled($context_key), fn ($builder) => $builder->where('context_key', $context_key))
                                ->ordered();
                        },
                    ])
                    ->get()
                    ->filter(function (ModuleDefinition $definition): bool {
                        return $definition->instances->isNotEmpty();
                    })
                    ->values();

                if ($definitions->isEmpty()) {
                    Log::channel('stack')->warning('Module runtime resolver returned no enabled module instances.', [
                        'placement'   => $placement,
                        'context_key' => $context_key,
                    ]);
                }

                return $definitions;
            },
        );

        return $resolved_modules;
    }
}
