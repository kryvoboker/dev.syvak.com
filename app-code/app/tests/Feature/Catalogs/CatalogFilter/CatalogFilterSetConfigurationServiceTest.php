<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterIndexStatusEnum;
use App\Jobs\RebuildCatalogFilterIndexJob;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\CatalogFilterIndexRebuildDispatcherService;
use App\Services\Catalogs\CatalogFilter\CatalogFilterSetConfigurationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogFilterSetConfigurationServiceTest extends TestCase
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

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createTables();

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_update_persists_set_groups_translations_and_marks_index_stale(): void
    {
        $filter_set = $this->createFilterSet();
        $group = CatalogFilterGroup::query()->create([
            'catalog_filter_set_id' => (int) $filter_set->id,
            'code' => 'price',
            'source_type' => 'price',
            'source_id' => null,
            'is_enabled' => true,
            'sort_order' => 10,
            'get_key' => 'price',
            'config' => ['custom_key' => 'preserved'],
        ]);

        DB::table('catalog_filter_group_translations')->insert([
            ['catalog_filter_group_id' => (int) $group->id, 'language_id' => 1, 'label' => 'Old price', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = app(CatalogFilterSetConfigurationService::class)->update($filter_set, [
            'context_types' => ['category', 'category'],
            'is_enabled' => true,
            'is_price_filter_enabled' => true,
            'is_attribute_filtering_enabled' => false,
            'price_source_mode' => 'both',
            'discount_only_policy' => 'exclude_without_discount',
            'facet_strategy' => 'self_excluding',
            'min_stock_quantity' => 1,
            'settings' => [],
            'filter_items' => [
                [
                    'code' => 'price',
                    'source_type' => 'price',
                    'source_id' => null,
                    'is_enabled' => true,
                    'sort_order' => 20,
                    'get' => ['key' => 'price', 'value' => '', 'extra' => []],
                    'config' => [
                        'mode' => 'range',
                        'min_price' => 10,
                        'max_price' => 100,
                        'step' => 5,
                        'labels' => ['en' => 'Price', 'uk' => 'Ціна'],
                    ],
                ],
            ],
        ]);

        $saved_set = $result['filter_set'];
        $saved_group = $group->fresh();

        $this->assertSame(['category'], $saved_set->context_types);
        $this->assertSame('category', (string) $saved_set->getRawOriginal('context_type'));
        $this->assertSame(20, (int) $saved_group?->sort_order);
        $this->assertSame('preserved', data_get($saved_group?->config, 'custom_key'));
        $this->assertSame('range', data_get($saved_group?->config, 'mode'));
        $this->assertSame(CatalogFilterIndexStatusEnum::Stale->value, DB::table('catalog_filter_index_meta')->value('last_status'));
        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $group->id,
            'language_id' => 1,
            'label' => 'Price',
        ]);
        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $group->id,
            'language_id' => 2,
            'label' => 'Ціна',
        ]);
    }

    public function test_queue_enabled_dispatches_unique_rebuild_job_and_marks_index_queued(): void
    {
        Bus::fake();
        config()->set('catalog-filter.rebuild.queue_enabled', true);

        $filter_set = $this->createFilterSet();
        $summary = app(CatalogFilterIndexRebuildDispatcherService::class)
            ->dispatch($filter_set);

        $this->assertSame('queued', $summary['status']);
        $this->assertSame('queued', DB::table('catalog_filter_index_meta')->value('last_status'));
        Bus::assertDispatched(RebuildCatalogFilterIndexJob::class, function (RebuildCatalogFilterIndexJob $job) use ($filter_set): bool {
            return $job->filter_set_id === (int) $filter_set->id;
        });
    }

    public function test_update_rolls_back_filter_set_when_group_persistence_fails(): void
    {
        $filter_set = $this->createFilterSet();
        $group = CatalogFilterGroup::query()->create([
            'catalog_filter_set_id' => (int) $filter_set->id,
            'code' => 'price',
            'source_type' => 'price',
            'source_id' => null,
            'is_enabled' => true,
            'sort_order' => 10,
            'get_key' => 'price',
            'config' => [],
        ]);

        DB::statement(
            "CREATE TRIGGER fail_catalog_filter_group_update BEFORE UPDATE ON catalog_filter_groups BEGIN SELECT RAISE(ABORT, 'forced catalog filter group failure'); END",
        );

        try {
            app(CatalogFilterSetConfigurationService::class)->update($filter_set, [
                'context_types' => ['search'],
                'is_enabled' => false,
                'filter_items' => [
                    [
                        'code' => 'price',
                        'source_type' => 'price',
                        'source_id' => null,
                        'is_enabled' => false,
                        'sort_order' => 99,
                        'get' => ['key' => 'price'],
                        'config' => ['mode' => 'range'],
                    ],
                ],
            ]);

            $this->fail('Expected catalog filter group update to fail.');
        } catch (\Throwable) {
            // The assertions below verify the transaction rollback.
        } finally {
            DB::statement('DROP TRIGGER fail_catalog_filter_group_update');
        }

        $this->assertSame('category', (string) $filter_set->fresh()?->getRawOriginal('context_type'));
        $this->assertTrue((bool) $filter_set->fresh()?->is_enabled);
        $this->assertSame(10, (int) $group->fresh()?->sort_order);
        $this->assertTrue((bool) $group->fresh()?->is_enabled);
    }

    private function createFilterSet(): CatalogFilterSet
    {
        $filter_set = CatalogFilterSet::query()->create([
            'code' => 'default_category',
            'context_type' => 'category',
            'context_types' => ['category'],
            'is_enabled' => true,
            'is_price_filter_enabled' => true,
            'is_attribute_filtering_enabled' => true,
            'price_source_mode' => 'both',
            'facet_strategy' => 'self_excluding',
            'discount_only_policy' => 'exclude_without_discount',
            'min_stock_quantity' => 1,
            'settings' => [],
        ]);

        DB::table('catalog_filter_index_meta')->insert([
            'catalog_filter_set_id' => (int) $filter_set->id,
            'index_version' => 1,
            'active_index_version' => 1,
            'last_status' => 'ok',
            'items_total' => 0,
            'values_total' => 0,
            'index_rows_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $filter_set;
    }

    private function createTables(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('catalog_filter_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('context_type', 40);
            $table->json('context_types')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_price_filter_enabled')->default(true);
            $table->boolean('is_attribute_filtering_enabled')->default(true);
            $table->string('price_source_mode', 40)->default('both');
            $table->string('facet_strategy', 40)->default('self_excluding');
            $table->string('discount_only_policy', 60)->default('exclude_without_discount');
            $table->unsignedInteger('min_stock_quantity')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('catalog_filter_groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_filter_set_id');
            $table->string('code', 120);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('get_key', 120)->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['catalog_filter_set_id', 'code']);
        });

        Schema::create('catalog_filter_group_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_filter_group_id');
            $table->unsignedBigInteger('language_id');
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['catalog_filter_group_id', 'language_id']);
        });

        Schema::create('catalog_filter_index_meta', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_filter_set_id')->unique();
            $table->unsignedBigInteger('index_version')->default(1);
            $table->unsignedBigInteger('active_index_version')->default(1);
            $table->unsignedBigInteger('building_index_version')->nullable();
            $table->string('rebuild_lock_key')->nullable();
            $table->timestamp('rebuild_lock_acquired_at')->nullable();
            $table->timestamp('last_full_rebuild_at')->nullable();
            $table->timestamp('last_incremental_sync_at')->nullable();
            $table->string('last_status')->default('ok');
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('last_progress_percent')->nullable();
            $table->string('last_run_mode')->nullable();
            $table->unsignedInteger('items_total')->default(0);
            $table->unsignedInteger('values_total')->default(0);
            $table->unsignedBigInteger('index_rows_total')->default(0);
            $table->timestamps();
        });
    }
}
