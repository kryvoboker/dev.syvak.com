<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Services\Modules\ModuleClassResolverService;
use App\Services\Modules\ModuleProviderResolverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleProviderResolverServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('cache.default', 'array');
        config()->set('modules-runtime.allowed_strategies', [
            'eager',
            'route_matched',
            'middleware_after_session',
        ]);
        config()->set('modules-runtime.default_strategy', 'route_matched');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Storage::fake('local');

        $module_class_resolver_service = new class () extends ModuleClassResolverService
        {
            public function resolve(ModuleDefinition|string|null $module_definition, string $relative_class): string
            {
                return sprintf(
                    'Modules\\%1$s\\%2$s',
                    $module_definition instanceof ModuleDefinition ? $module_definition->nwidart_name : (string) $module_definition,
                    ltrim($relative_class, '\\'),
                );
            }
        };

        $this->app->instance(ModuleClassResolverService::class, $module_class_resolver_service);

        $this->createModuleDefinitionsTable();
        $this->createModuleInstancesTable();
    }

    public function test_resolver_returns_route_matched_provider_for_carousel_strategy(): void
    {
        $definition = ModuleDefinition::query()->create([
            'name'                     => 'Carousel',
            'slug'                     => 'carousel-route-matched',
            'nwidart_name'             => 'Carousel',
            'module_path'              => base_path('Modules/Carousel'),
            'description'              => 'Carousel module',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 1,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Carousel Home',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => [
                'shared' => [
                    'page_types' => ['home'],
                ],
            ],
            'meta' => [],
        ]);

        $request = $this->makeAdminRequest();

        $resolver = $this->app->make(ModuleProviderResolverService::class);

        $route_matched_providers = $resolver->resolveForStrategy('route_matched', $request);
        $eager_providers         = $resolver->resolveForStrategy('eager', $request);

        $this->assertContains('Modules\\Carousel\\Providers\\CarouselServiceProvider', $route_matched_providers);
        $this->assertNotContains('Modules\\Carousel\\Providers\\CarouselServiceProvider', $eager_providers);
    }

    public function test_resolver_returns_route_matched_provider_for_products_carousel_strategy(): void
    {
        $definition = ModuleDefinition::query()->create([
            'name'                     => 'ProductsCarousel',
            'slug'                     => 'products-carousel-route-matched',
            'nwidart_name'             => 'ProductsCarousel',
            'module_path'              => base_path('Modules/ProductsCarousel'),
            'description'              => 'Products Carousel module',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 2,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Products Carousel Home',
            'placement'   => 'home',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => [
                'shared' => [
                    'page_types' => ['home'],
                ],
            ],
            'meta' => [],
        ]);

        $request  = $this->makeAdminRequest();
        $resolver = $this->app->make(ModuleProviderResolverService::class);

        $route_matched_providers = $resolver->resolveForStrategy('route_matched', $request);
        $eager_providers         = $resolver->resolveForStrategy('eager', $request);

        $this->assertContains('Modules\\ProductsCarousel\\Providers\\ProductsCarouselServiceProvider', $route_matched_providers);
        $this->assertNotContains('Modules\\ProductsCarousel\\Providers\\ProductsCarouselServiceProvider', $eager_providers);
    }

    public function test_resolver_returns_middleware_after_session_provider_for_module_override(): void
    {
        $module_name = 'SessionModule';
        $module_path = $this->createFakeModuleConfig($module_name, 'middleware_after_session');

        $definition = ModuleDefinition::query()->create([
            'name'                     => $module_name,
            'slug'                     => Str::kebab($module_name),
            'nwidart_name'             => $module_name,
            'module_path'              => $module_path,
            'description'              => 'Fake module',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 2,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Session Module Home',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => [
                'shared' => [
                    'page_types' => ['home'],
                ],
            ],
            'meta' => [],
        ]);

        $request = $this->makeAdminRequest();

        $resolver = $this->app->make(ModuleProviderResolverService::class);

        $middleware_providers = $resolver->resolveForStrategy('middleware_after_session', $request);
        $route_providers      = $resolver->resolveForStrategy('route_matched', $request);

        $expected_provider = 'Modules\\SessionModule\\Providers\\SessionModuleServiceProvider';

        $this->assertContains($expected_provider, $middleware_providers);
        $this->assertNotContains($expected_provider, $route_providers);
    }

    public function test_invalid_module_strategy_falls_back_to_default_strategy(): void
    {
        $module_name = 'FallbackModule';
        $module_path = $this->createFakeModuleConfig($module_name, 'broken_strategy');

        $definition = ModuleDefinition::query()->create([
            'name'                     => $module_name,
            'slug'                     => Str::kebab($module_name),
            'nwidart_name'             => $module_name,
            'module_path'              => $module_path,
            'description'              => 'Fallback module',
            'is_installed'             => true,
            'is_enabled'               => true,
            'is_enabled_in_filesystem' => true,
            'sort_order'               => 3,
            'settings_schema'          => [],
            'meta'                     => [],
        ]);

        $definition->instances()->create([
            'name'        => 'Fallback Module Home',
            'placement'   => 'hero',
            'context_key' => null,
            'is_enabled'  => true,
            'sort_order'  => 1,
            'settings'    => [
                'shared' => [
                    'page_types' => ['home'],
                ],
            ],
            'meta' => [],
        ]);

        $request  = $this->makeAdminRequest();
        $resolver = $this->app->make(ModuleProviderResolverService::class);

        $route_providers = $resolver->resolveForStrategy('route_matched', $request);

        $this->assertContains('Modules\\FallbackModule\\Providers\\FallbackModuleServiceProvider', $route_providers);
    }

    private function makeAdminRequest(): Request
    {
        $request = Request::create('/en/alyo-admin', 'GET');
        $route   = Route::get('/en/alyo-admin-' . Str::random(8), fn (): string => 'ok')
            ->name('filament.admin.pages.dashboard');

        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function createFakeModuleConfig(string $module_name, string $strategy): string
    {
        $relative_path = sprintf('modules/%s/config/config.php', $module_name);

        Storage::disk('local')->put($relative_path, sprintf(
            "<?php\n\nreturn %s;\n",
            var_export([
                'runtime' => [
                    'provider_loading_strategy' => $strategy,
                ],
            ], true),
        ));

        return Storage::disk('local')->path(sprintf('modules/%s', $module_name));
    }

    private function createModuleDefinitionsTable(): void
    {
        Schema::create('module_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('nwidart_name', 255)->unique();
            $table->string('module_path', 1000)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_enabled_in_filesystem')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_schema')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    private function createModuleInstancesTable(): void
    {
        Schema::create('module_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_definition_id')->constrained('module_definitions')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('placement', 255)->nullable();
            $table->string('context_key', 255)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
