<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\CategoryPageFilterSyncService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryPageSettingsServicesTest extends TestCase
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

        $this->createLanguagesTable();
        $this->createPageSettingsTables();
        $this->createCategoriesTables();
        $this->createAttributesTables();
        $this->createProductsTables();

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_bootstrap_creates_category_page_settings_with_defaults(): void
    {
        $service = app(PageSettingsBootstrapService::class);

        $page_setting = $service->bootstrapCategoryPageSetting();

        $this->assertSame(PageSetting::PAGE_TYPE_CATEGORY, $page_setting->page_type);
        $this->assertSame(2, (int) data_get($page_setting->settings, 'meta.contract_version'));
        $this->assertTrue((bool) data_get($page_setting->settings, 'ui.sorting.enabled'));
        $this->assertFalse((bool) data_get($page_setting->settings, 'ui.filtering.enabled'));

        $sorting_items = (array) data_get($page_setting->settings, 'items.sorting', []);
        $this->assertCount(5, $sorting_items);
        $this->assertTrue(collect($sorting_items)->contains(fn (array $item): bool => (string) Arr::get($item, 'code') === 'newest'));

        $localized = (array) data_get($page_setting->settings, 'localized', []);
        $this->assertArrayHasKey('1', $localized);
        $this->assertArrayHasKey('2', $localized);
        $this->assertArrayNotHasKey('option_labels', (array) Arr::get($localized, '1', []));
    }

    public function test_filter_sync_is_idempotent_and_generates_expected_get_contracts(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();

        DB::table('categories')->insert([
            'id' => 11,
            'parent_id' => null,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_descriptions')->insert([
            'category_id' => 11,
            'language_id' => 1,
            'name' => 'Roses',
            'description' => null,
            'h1_title' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attributes')->insert([
            'id' => 21,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_descriptions')->insert([
            'attribute_id' => 21,
            'language_id' => 1,
            'name' => 'Color',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 31,
            'model' => 'M-31',
            'sku' => 'SKU-31',
            'ean' => 31,
            'quantity' => 15,
            'minimum' => 1,
            'image' => null,
            'price' => 100,
            'viewed' => 0,
            'is_active' => true,
            'date_available' => now(),
            'date_added' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_product')->insert([
            'category_id' => 11,
            'product_id' => 31,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variants')->insert([
            'id' => 41,
            'product_id' => 31,
            'is_default' => true,
            'is_active' => true,
            'quantity' => 15,
            'minimum' => 1,
            'price' => 100,
            'date_available' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variant_attribute_values')->insert([
            'product_variant_id' => 41,
            'attribute_id' => 21,
            'language_id' => 1,
            'value_string' => 'Red',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variant_discounts')->insert([
            'product_variant_id' => 41,
            'user_group_id' => null,
            'quantity' => null,
            'priority' => 1,
            'price' => 80,
            'date_start' => now()->subDay(),
            'date_end' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sync_service = app(CategoryPageFilterSyncService::class);

        $first_summary = $sync_service->sync($page_setting);
        $second_summary = $sync_service->sync($page_setting);

        $this->assertGreaterThan(0, $first_summary['created_count']);
        $this->assertSame(0, $second_summary['created_count']);

        $page_setting->refresh();

        $filter_items = collect((array) data_get($page_setting->settings, 'items.filters', []));

        $this->assertTrue($filter_items->contains(fn (array $item): bool => (string) Arr::get($item, 'code') === 'price'));
        $this->assertTrue($filter_items->contains(fn (array $item): bool => (string) Arr::get($item, 'code') === 'stock'));
        $this->assertFalse($filter_items->contains(fn (array $item): bool => (string) Arr::get($item, 'code') === 'category_11'));
        $this->assertTrue($filter_items->contains(fn (array $item): bool => (string) Arr::get($item, 'code') === 'attribute_21'));

        $price_item = $filter_items
            ->first(fn (array $item): bool => (string) Arr::get($item, 'code') === 'price');

        $this->assertIsArray($price_item);
        $this->assertSame('price', (string) Arr::get($price_item, 'get.key'));
        $this->assertSame('price_from', (string) Arr::get($price_item, 'get.extra.from_key'));
        $this->assertSame('price_to', (string) Arr::get($price_item, 'get.extra.to_key'));
        $this->assertSame(80.0, (float) Arr::get($price_item, 'config.min_price'));
        $this->assertContains(
            (string) Arr::get($price_item, 'config.mode'),
            array_keys((array) config('app.page_settings.category.filter_modes', [])),
        );
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

    private function createPageSettingsTables(): void
    {
        Schema::create('page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_type', 100)->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    private function createCategoriesTables(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('category_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('h1_title')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'language_id']);
        });

        Schema::create('category_product', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
            $table->unique(['product_id', 'category_id']);
        });
    }

    private function createAttributesTables(): void
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

    private function createProductsTables(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('model', 255);
            $table->string('sku', 255);
            $table->unsignedBigInteger('ean');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->string('image')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('is_active')->default(false);
            $table->dateTime('date_available')->nullable();
            $table->dateTime('date_added')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->string('image')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->dateTime('date_available')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variant_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->timestamps();
        });
    }
}
