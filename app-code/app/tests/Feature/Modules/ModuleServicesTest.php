<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Services\Modules\ModuleDefinitionSyncService;
use App\Services\Modules\ModuleDiscoveryService;
use App\Services\Modules\ModuleInstanceService;
use App\Services\Modules\ModuleRuntimeResolverService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createModuleDefinitionsTable();
        $this->createModuleInstancesTable();
    }

    public function test_module_definition_sync_service_creates_updates_and_marks_missing_records(): void
    {
        $existing_definition = ModuleDefinition::query()->create([
            'name' => 'Carousel Legacy',
            'slug' => 'carousel',
            'nwidart_name' => 'Carousel',
            'module_path' => '/legacy/modules/Carousel',
            'description' => 'Legacy description',
            'is_installed' => true,
            'is_enabled' => false,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 1,
            'settings_schema' => ['legacy' => 'keep'],
            'meta' => ['owner' => 'catalog'],
        ]);

        ModuleDefinition::query()->create([
            'name' => 'Missing Module',
            'slug' => 'missing-module',
            'nwidart_name' => 'MissingModule',
            'module_path' => '/legacy/modules/MissingModule',
            'description' => 'Should become missing',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 2,
            'settings_schema' => [],
            'meta' => [],
        ]);

        $discovered_modules = collect([
            [
                'name' => 'Carousel',
                'slug' => 'carousel',
                'nwidart_name' => 'Carousel',
                'module_path' => '/var/modules/Carousel',
                'description' => 'Fresh description',
                'is_installed' => true,
                'is_enabled_in_filesystem' => true,
                'settings_schema' => ['layout' => 'slider'],
                'meta' => ['priority' => '10'],
            ],
            [
                'name' => 'Hero Banner',
                'slug' => 'hero-banner',
                'nwidart_name' => 'HeroBanner',
                'module_path' => '/var/modules/HeroBanner',
                'description' => 'Hero banner module',
                'is_installed' => true,
                'is_enabled_in_filesystem' => false,
                'settings_schema' => ['variant' => 'full-width'],
                'meta' => ['priority' => '20'],
            ],
        ]);

        $this->app->instance(ModuleDiscoveryService::class, new class ($discovered_modules) extends ModuleDiscoveryService {
            public function __construct(
                private readonly Collection $discovered_modules,
            ) {
            }

            public function discover(): Collection
            {
                return $this->discovered_modules;
            }
        });

        $summary = $this->app->make(ModuleDefinitionSyncService::class)->sync();

        $this->assertSame([
            'created' => 1,
            'updated' => 1,
            'missing' => 1,
        ], $summary);

        $existing_definition->refresh();
        $existing_definition_settings_schema = is_array($existing_definition->settings_schema) ? $existing_definition->settings_schema : [];
        $existing_definition_meta = is_array($existing_definition->meta) ? $existing_definition->meta : [];

        $this->assertFalse($existing_definition->is_enabled);
        $this->assertSame('/var/modules/Carousel', $existing_definition->module_path);
        $this->assertSame('Fresh description', $existing_definition->description);
        $this->assertArrayHasKey('legacy', $existing_definition_settings_schema);
        $this->assertArrayHasKey('layout', $existing_definition_settings_schema);
        $this->assertArrayHasKey('owner', $existing_definition_meta);
        $this->assertArrayHasKey('priority', $existing_definition_meta);
        /** @var array{legacy: string, layout: string} $existing_definition_settings_schema */
        /** @var array{owner: string, priority: string} $existing_definition_meta */
        $this->assertSame('keep', $existing_definition_settings_schema['legacy']);
        $this->assertSame('slider', $existing_definition_settings_schema['layout']);
        $this->assertSame('catalog', $existing_definition_meta['owner']);
        $this->assertSame('10', $existing_definition_meta['priority']);

        $this->assertDatabaseHas('module_definitions', [
            'nwidart_name' => 'HeroBanner',
            'slug' => 'hero-banner',
            'is_enabled' => false,
            'is_enabled_in_filesystem' => false,
        ]);

        $this->assertDatabaseHas('module_definitions', [
            'nwidart_name' => 'MissingModule',
            'is_installed' => false,
            'is_enabled_in_filesystem' => false,
        ]);
    }

    public function test_module_instance_service_can_toggle_definition_and_duplicate_isolated_instances(): void
    {
        $definition = ModuleDefinition::query()->create([
            'name' => 'Promo Banner',
            'slug' => 'promo-banner',
            'nwidart_name' => 'PromoBanner',
            'module_path' => '/var/modules/PromoBanner',
            'description' => 'Promo Banner',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 1,
            'settings_schema' => [],
            'meta' => [],
        ]);

        $service = $this->app->make(ModuleInstanceService::class);

        $service->setGlobalState($definition, false);

        $definition->refresh();

        $this->assertFalse($definition->is_enabled);

        $instance = $service->createFromDefinition($definition, [
            'name' => 'Promo Banner Home',
            'placement' => 'home.hero',
            'context_key' => 'homepage',
            'settings' => ['slides' => '5'],
            'meta' => ['theme' => 'dark'],
        ]);

        $duplicate = $service->duplicate($instance, [
            'name' => 'Promo Banner Product',
            'context_key' => 'product.card',
        ]);

        $service->update($duplicate, [
            'settings' => [
                'slides' => '2',
            ],
        ]);

        $instance->refresh();
        $duplicate->refresh();
        $instance_settings = is_array($instance->settings) ? $instance->settings : [];
        $duplicate_settings = is_array($duplicate->settings) ? $duplicate->settings : [];

        $this->assertNotSame($instance->id, $duplicate->id);
        $this->assertSame('homepage', $instance->context_key);
        $this->assertSame('product.card', $duplicate->context_key);
        $this->assertArrayHasKey('slides', $instance_settings);
        $this->assertArrayHasKey('slides', $duplicate_settings);
        /** @var array{slides: string} $instance_settings */
        /** @var array{slides: string} $duplicate_settings */
        $this->assertSame('5', $instance_settings['slides']);
        $this->assertSame('2', $duplicate_settings['slides']);
    }

    public function test_module_runtime_resolver_returns_only_enabled_instances_for_requested_context(): void
    {
        $enabled_definition = ModuleDefinition::query()->create([
            'name' => 'Carousel',
            'slug' => 'carousel',
            'nwidart_name' => 'Carousel',
            'module_path' => '/var/modules/Carousel',
            'description' => 'Carousel',
            'is_installed' => true,
            'is_enabled' => true,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 1,
            'settings_schema' => [],
            'meta' => [],
        ]);

        $disabled_definition = ModuleDefinition::query()->create([
            'name' => 'Hero Banner',
            'slug' => 'hero-banner',
            'nwidart_name' => 'HeroBanner',
            'module_path' => '/var/modules/HeroBanner',
            'description' => 'Hero',
            'is_installed' => true,
            'is_enabled' => false,
            'is_enabled_in_filesystem' => true,
            'sort_order' => 2,
            'settings_schema' => [],
            'meta' => [],
        ]);

        $enabled_definition->instances()->create([
            'name' => 'Homepage Carousel',
            'placement' => 'home.hero',
            'context_key' => 'homepage',
            'is_enabled' => true,
            'sort_order' => 1,
            'settings' => ['slides' => '5'],
            'meta' => [],
        ]);

        $enabled_definition->instances()->create([
            'name' => 'Disabled Carousel',
            'placement' => 'home.hero',
            'context_key' => 'homepage',
            'is_enabled' => false,
            'sort_order' => 2,
            'settings' => ['slides' => '6'],
            'meta' => [],
        ]);

        $disabled_definition->instances()->create([
            'name' => 'Disabled Definition Instance',
            'placement' => 'home.hero',
            'context_key' => 'homepage',
            'is_enabled' => true,
            'sort_order' => 1,
            'settings' => ['slides' => '1'],
            'meta' => [],
        ]);

        $resolved_modules = $this->app
            ->make(ModuleRuntimeResolverService::class)
            ->resolve('home.hero', 'homepage');

        $this->assertCount(1, $resolved_modules);
        $this->assertSame('carousel', $resolved_modules->first()->slug);
        $this->assertCount(1, $resolved_modules->first()->instances);
        $this->assertSame('Homepage Carousel', $resolved_modules->first()->instances->first()->name);
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
