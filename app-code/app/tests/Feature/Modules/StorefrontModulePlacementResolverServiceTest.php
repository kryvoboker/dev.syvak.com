<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Services\Modules\ModuleCacheService;
use App\Services\Modules\ModuleRuntimeResolverService;
use App\Services\Modules\StorefrontModulePlacementResolverService;
use Closure;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StorefrontModulePlacementResolverServiceTest extends TestCase
{
    public function test_it_resolves_render_ready_items_for_a_placement(): void
    {
        require_once base_path('tests/Fixtures/Modules/FakeModule/Services/FakeModuleModuleDataService.php');

        $this->mockRuntimeResolver(collect([
            $this->makeModuleDefinition(
                id: 501,
                nwidart_name: 'FakeModule',
                module_path: base_path('tests/Fixtures/Modules/FakeModule'),
            ),
        ]));

        $resolver = app(StorefrontModulePlacementResolverService::class);
        $items = $resolver->resolveForPlacement('top', 'home');

        $this->assertCount(2, $items);
        $this->assertSame(501, $items[0]['module_definition_id']);
        $this->assertSame('FakeModule', $items[0]['module_name']);
        $this->assertSame('catalog.pages.home', $items[0]['view']);
        $this->assertSame(7001, $items[0]['view_data']['fake_module_data']['instance_id']);
        $this->assertSame('top', $items[0]['view_data']['fake_module_data']['placement']);
        $this->assertSame('home', $items[0]['view_data']['fake_module_data']['page_type']);
        $this->assertSame('home', $items[0]['view_data']['page_type']);
    }

    public function test_it_skips_module_when_data_service_contract_is_not_resolvable(): void
    {
        $this->mockRuntimeResolver(collect([
            $this->makeModuleDefinition(
                id: 777,
                nwidart_name: 'BrokenModule',
                module_path: base_path('tests/Fixtures/Modules/BrokenModule'),
            ),
        ]));

        $resolver = app(StorefrontModulePlacementResolverService::class);
        $items = $resolver->resolveForPlacement('bottom', 'home');

        $this->assertSame([], $items);
    }

    /**
     * @param  Collection<int, ModuleDefinition>  $definitions
     */
    private function mockRuntimeResolver(Collection $definitions): void
    {
        $module_cache_service = new class ($definitions) extends ModuleCacheService {
            public ?string $last_key = null;

            /**
             * @param  Collection<int, ModuleDefinition>  $definitions
             */
            public function __construct(
                private readonly Collection $definitions,
            ) {
            }

            public function remember(string $key, Closure $callback, int $ttl_seconds = 3600): mixed
            {
                $this->last_key = $key;
                unset($callback, $ttl_seconds);

                return $this->definitions;
            }
        };

        $runtime_resolver = new ModuleRuntimeResolverService($module_cache_service);

        $this->app->instance(ModuleRuntimeResolverService::class, $runtime_resolver);
    }

    private function makeModuleDefinition(int $id, string $nwidart_name, string $module_path): ModuleDefinition
    {
        $definition = new ModuleDefinition();
        $definition->forceFill([
            'id' => $id,
            'nwidart_name' => $nwidart_name,
            'module_path' => $module_path,
        ]);

        return $definition;
    }
}
