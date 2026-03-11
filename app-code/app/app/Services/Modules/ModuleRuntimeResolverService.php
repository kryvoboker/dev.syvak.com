<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

readonly class ModuleRuntimeResolverService
{
    public function __construct(
        private ModuleCacheService $module_cache_service,
    ) {}

    /**
     * @return Collection<int, ModuleDefinition>
     */
    public function resolve(?string $placement = null, ?string $context_key = null): Collection
    {
        $modules_placements = config('app.modules_placements');

        if (in_array($placement, $modules_placements)) {
            $placement = array_search($placement, $modules_placements);;
        }

        $cache_key = 'runtime:' . ($placement ?? 'all') . ':' . ($context_key ?? 'all');

        /** @var Collection<int, ModuleDefinition> $resolved_modules */
        $resolved_modules = $this->module_cache_service->remember(
            $cache_key,
            function () use ($placement, $context_key): Collection {
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
