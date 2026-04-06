<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\CatalogFilter;

use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\FilterGroupGeneratorService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogFilterGroupGeneratorServiceTest extends TestCase
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

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createLanguagesTable();
        $this->createCatalogFilterTables();
        $this->createCatalogSourceTables();

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function test_sync_preserves_user_managed_fields_for_existing_system_group(): void
    {
        $filter_set = CatalogFilterSet::query()->create([
            'code'                           => 'default_category',
            'context_type'                   => 'category',
            'context_types'                  => ['category'],
            'is_enabled'                     => true,
            'is_price_filter_enabled'        => true,
            'is_attribute_filtering_enabled' => true,
            'price_source_mode'              => 'both',
            'facet_strategy'                 => 'self_excluding',
            'discount_only_policy'           => 'exclude_without_discount',
            'min_stock_quantity'             => 1,
            'settings'                       => [],
        ]);

        DB::table('catalog_filter_groups')->insert([
            'id'                    => 10,
            'catalog_filter_set_id' => (int) $filter_set->id,
            'code'                  => 'price',
            'source_type'           => 'stock',
            'source_id'             => 501,
            'is_enabled'            => false,
            'sort_order'            => 777,
            'get_key'               => 'custom_price_key',
            'config'                => json_encode([
                'mode' => 'range',
                'get'  => [
                    'value' => 'custom',
                    'extra' => ['from_key' => 'custom_from'],
                ],
                'min_price' => 12,
                'max_price' => 120,
                'step'      => 3,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(FilterGroupGeneratorService::class)->sync($filter_set);

        $price_group = CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->where('code', 'price')
            ->first();

        $this->assertNotNull($price_group);
        $this->assertSame('price', (string) $price_group->getRawOriginal('source_type'));
        $this->assertNull($price_group->source_id);

        $this->assertFalse((bool) $price_group->is_enabled);
        $this->assertSame(777, (int) $price_group->sort_order);
        $this->assertSame('custom_price_key', (string) $price_group->get_key);

        $config = (array) $price_group->config;

        $this->assertSame('range', (string) ($config['mode'] ?? ''));
        $this->assertSame('custom', (string) data_get($config, 'get.value', ''));
        $this->assertSame('custom_from', (string) data_get($config, 'get.extra.from_key', ''));
        $this->assertSame(12, (int) ($config['min_price'] ?? 0));

        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $price_group->id,
            'language_id'             => 1,
            'label'                   => 'Price',
        ]);
        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $price_group->id,
            'language_id'             => 2,
            'label'                   => 'Ціна',
        ]);
    }

    public function test_sync_creates_attribute_group_with_translations_for_active_attribute_values(): void
    {
        $filter_set = CatalogFilterSet::query()->create([
            'code'                           => 'default_category',
            'context_type'                   => 'category',
            'context_types'                  => ['category'],
            'is_enabled'                     => true,
            'is_price_filter_enabled'        => true,
            'is_attribute_filtering_enabled' => true,
            'price_source_mode'              => 'both',
            'facet_strategy'                 => 'self_excluding',
            'discount_only_policy'           => 'exclude_without_discount',
            'min_stock_quantity'             => 1,
            'settings'                       => [],
        ]);

        DB::table('attributes')->insert([
            'id'         => 21,
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_descriptions')->insert([
            ['attribute_id' => 21, 'language_id' => 1, 'name' => 'Age', 'created_at' => now(), 'updated_at' => now()],
            ['attribute_id' => 21, 'language_id' => 2, 'name' => 'Вік', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('products')->insert([
            'id'         => 31,
            'model'      => 'P-31',
            'sku'        => 'SKU-31',
            'ean'        => 31,
            'quantity'   => 15,
            'minimum'    => 1,
            'price'      => 100,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'id'         => 41,
            'product_id' => 31,
            'is_default' => true,
            'is_active'  => true,
            'quantity'   => 10,
            'minimum'    => 1,
            'price'      => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variant_attribute_values')->insert([
            'product_variant_id' => 41,
            'attribute_id'       => 21,
            'language_id'        => 1,
            'value_string'       => '7+',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        app(FilterGroupGeneratorService::class)->sync($filter_set);

        $attribute_group = CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->where('code', 'attribute_21')
            ->first();

        $this->assertNotNull($attribute_group);
        $this->assertSame('attribute', (string) $attribute_group->getRawOriginal('source_type'));
        $this->assertSame(21, (int) $attribute_group->source_id);
        $this->assertSame('filters[21]', (string) $attribute_group->get_key);

        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $attribute_group->id,
            'language_id'             => 1,
            'label'                   => 'Age',
        ]);
        $this->assertDatabaseHas('catalog_filter_group_translations', [
            'catalog_filter_group_id' => (int) $attribute_group->id,
            'language_id'             => 2,
            'label'                   => 'Вік',
        ]);
    }

    private function createLanguagesTable(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    private function createCatalogFilterTables(): void
    {
        Schema::create('catalog_filter_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('context_type', 40);
            $table->json('context_types')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_price_filter_enabled')->default(true);
            $table->boolean('is_attribute_filtering_enabled')->default(true);
            $table->string('price_source_mode', 40)->default('both');
            $table->string('discount_only_policy', 60)->default('exclude_without_discount');
            $table->string('facet_strategy', 40)->default('self_excluding');
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
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['catalog_filter_group_id', 'language_id']);
        });
    }

    private function createCatalogSourceTables(): void
    {
        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->unique(['attribute_id', 'language_id']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('model', 64)->nullable();
            $table->string('sku', 64)->nullable();
            $table->unsignedBigInteger('ean')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('quantity')->default(0);
            $table->integer('minimum')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('product_variant_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('value_string', 3000)->nullable();
            $table->timestamps();
            $table->unique(['product_variant_id', 'attribute_id', 'language_id']);
        });
    }
}
