<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\CatalogFilter;

use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\FilterValueGeneratorService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FilterValueGeneratorServiceTest extends TestCase
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
     */
    public function test_sync_generates_dash_separated_codes_for_attribute_values(): void
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

        $group = CatalogFilterGroup::query()->create([
            'catalog_filter_set_id' => (int) $filter_set->id,
            'code'                  => 'attribute_7',
            'source_type'           => 'attribute',
            'source_id'             => 7,
            'is_enabled'            => true,
            'sort_order'            => 100,
            'get_key'               => 'filters[7]',
            'config'                => [],
        ]);

        DB::table('products')->insert([
            ['id' => 101, 'model' => 'P-101', 'sku' => 'SKU-101', 'ean' => 101, 'quantity' => 10, 'minimum' => 1, 'price' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'model' => 'P-102', 'sku' => 'SKU-102', 'ean' => 102, 'quantity' => 10, 'minimum' => 1, 'price' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('product_to_attributes')->insert([
            ['product_id' => 101, 'attribute_id' => 7, 'language_id' => 1, 'text' => '100 г', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 102, 'attribute_id' => 7, 'language_id' => 1, 'text' => '50 см', 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(FilterValueGeneratorService::class)->sync($filter_set);

        $codes = DB::table('catalog_filter_values')
            ->where('catalog_filter_group_id', (int) $group->id)
            ->pluck('code')
            ->map(static fn (mixed $code): string => (string) $code)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['100-g', '50-sm'], $codes);

        $this->assertDatabaseMissing('catalog_filter_values', [
            'catalog_filter_group_id' => (int) $group->id,
            'code'                    => '100_g',
        ]);

        $this->assertDatabaseMissing('catalog_filter_values', [
            'catalog_filter_group_id' => (int) $group->id,
            'code'                    => '50_sm',
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

        Schema::create('catalog_filter_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_filter_group_id');
            $table->string('code', 140);
            $table->string('value_type', 20);
            $table->string('value_string', 300)->nullable();
            $table->decimal('value_number', 12, 4)->nullable();
            $table->decimal('range_from', 12, 4)->nullable();
            $table->decimal('range_to', 12, 4)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('products_count_cached')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['catalog_filter_group_id', 'code']);
        });

        Schema::create('catalog_filter_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_filter_value_id');
            $table->unsignedBigInteger('language_id');
            $table->string('label', 255);
            $table->timestamps();
            $table->unique(['catalog_filter_value_id', 'language_id']);
        });
    }

    private function createCatalogSourceTables(): void
    {
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

        Schema::create('product_to_attributes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('text', 3000)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_id', 'language_id']);
        });
    }
}
