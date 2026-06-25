<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

readonly class ModuleRuntimeResolverService
{
    public function __construct(
        private ModuleCacheService $module_cache_service,
    ) {
    }

    /**
     * @return Collection<int, ModuleDefinition>
     */
    public function resolve(?string $placement = null, ?string $context_key = null): Collection
    {
        $placement = $this->normalizePlacement($placement);

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

    private function normalizePlacement(?string $placement): ?string
    {
        if (blank($placement)) {
            return null;
        }

        $modules_placements = config('app.modules_placements', []);

        if (! is_array($modules_placements)) {
            return $placement;
        }

        $placement_keys       = array_map('strval', array_keys($modules_placements));
        $normalized_placement = Str::lower(trim($placement));

        foreach ($modules_placements as $placement_key => $placement_label) {
            if (
                Str::lower((string) $placement_key) === $normalized_placement
                || Str::lower((string) $placement_label) === $normalized_placement
            ) {
                return (string) $placement_key;
            }
        }

        Log::channel('stack')->warning('Module runtime resolver received unsupported placement value.', [
            'placement'          => $placement,
            'allowed_placements' => $placement_keys,
        ]);

        return $placement;
    }
}
