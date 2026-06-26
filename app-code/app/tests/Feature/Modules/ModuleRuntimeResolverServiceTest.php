<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Services\Modules\ModuleCacheService;
use App\Services\Modules\ModuleRuntimeResolverService;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ModuleRuntimeResolverServiceTest extends TestCase
{
    public function test_it_normalizes_human_readable_placement_label_to_key(): void
    {
        $module_cache_service = new class() extends ModuleCacheService
        {
            public ?string $last_key = null;

            public function remember(string $key, Closure $callback, int $ttl_seconds = 3600): mixed
            {
                unset($callback, $ttl_seconds);

                $this->last_key = $key;

                return new Collection();
            }
        };

        $runtime_resolver = new ModuleRuntimeResolverService($module_cache_service);

        $runtime_resolver->resolve('Top', null);

        $this->assertSame('runtime:top:all', $module_cache_service->last_key);
    }

    public function test_it_logs_warning_for_unsupported_placement_value(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with(
                'Module runtime resolver received unsupported placement value.',
                Mockery::on(function (array $context): bool {
                    return ($context['placement'] ?? null) === 'center'
                        && ($context['allowed_placements'] ?? null) === ['top', 'bottom'];
                }),
            );

        $module_cache_service = new class() extends ModuleCacheService
        {
            public ?string $last_key = null;

            public function remember(string $key, Closure $callback, int $ttl_seconds = 3600): mixed
            {
                unset($callback, $ttl_seconds);

                $this->last_key = $key;

                return new Collection();
            }
        };

        $runtime_resolver = new ModuleRuntimeResolverService($module_cache_service);

        $runtime_resolver->resolve('center', null);

        $this->assertSame('runtime:center:all', $module_cache_service->last_key);
    }
}
