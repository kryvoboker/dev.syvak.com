<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\CategoryPageFilterSyncService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Schema\Blueprint;
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
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
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
        $this->assertTrue($page_setting->is_sorting_enabled);
        $this->assertTrue($page_setting->is_filtering_enabled);
        $this->assertSame(1, data_get($page_setting->settings, 'meta.contract_version'));

        $this->assertDatabaseCount('page_setting_items', 5);
        $this->assertDatabaseHas('page_setting_items', [
            'page_setting_id' => $page_setting->id,
            'type'            => PageSetting::ITEM_TYPE_SORTING,
            'code'            => 'newest',
        ]);
        $this->assertDatabaseCount('page_setting_translations', 2);

        $translation_content = DB::table('page_setting_translations')
            ->where('page_setting_id', $page_setting->id)
            ->where('language_id', 1)
            ->value('content');

        $this->assertIsString($translation_content);

        $decoded_translation_content = json_decode($translation_content, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('option_labels', $decoded_translation_content);
    }

    public function test_filter_sync_is_idempotent_and_generates_expected_get_contracts(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();

        DB::table('categories')->insert([
            'id'         => 11,
            'parent_id'  => null,
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_descriptions')->insert([
            'category_id'      => 11,
            'language_id'      => 1,
            'name'             => 'Roses',
            'description'      => null,
            'h1_title'         => null,
            'meta_title'       => null,
            'meta_description' => null,
            'meta_keywords'    => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('attributes')->insert([
            'id'         => 21,
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_descriptions')->insert([
            'attribute_id' => 21,
            'language_id'  => 1,
            'name'         => 'Color',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('products')->insert([
            'id'             => 31,
            'model'          => 'M-31',
            'sku'            => 'SKU-31',
            'ean'            => 31,
            'quantity'       => 15,
            'minimum'        => 1,
            'image'          => null,
            'price'          => 100,
            'viewed'         => 0,
            'is_active'      => true,
            'date_available' => now(),
            'date_added'     => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('category_product')->insert([
            'category_id' => 11,
            'product_id'  => 31,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('product_to_attributes')->insert([
            'product_id'   => 31,
            'attribute_id' => 21,
            'language_id'  => 1,
            'text'         => 'Red',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('product_discounts')->insert([
            'product_id'    => 31,
            'user_group_id' => null,
            'quantity'      => null,
            'priority'      => 1,
            'price'         => 80,
            'date_start'    => now()->subDay(),
            'date_end'      => now()->addDay(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $sync_service = app(CategoryPageFilterSyncService::class);

        $first_summary  = $sync_service->sync($page_setting);
        $second_summary = $sync_service->sync($page_setting);

        $this->assertGreaterThan(0, $first_summary['created_count']);
        $this->assertSame(0, $second_summary['created_count']);

        $this->assertDatabaseHas('page_setting_items', [
            'page_setting_id' => $page_setting->id,
            'type'            => PageSetting::ITEM_TYPE_FILTER,
            'code'            => 'price',
        ]);
        $this->assertDatabaseHas('page_setting_items', [
            'page_setting_id' => $page_setting->id,
            'type'            => PageSetting::ITEM_TYPE_FILTER,
            'code'            => 'stock',
        ]);
        $this->assertDatabaseMissing('page_setting_items', [
            'page_setting_id' => $page_setting->id,
            'type'            => PageSetting::ITEM_TYPE_FILTER,
            'code'            => 'category_11',
        ]);
        $this->assertDatabaseHas('page_setting_items', [
            'page_setting_id' => $page_setting->id,
            'type'            => PageSetting::ITEM_TYPE_FILTER,
            'code'            => 'attribute_21',
        ]);

        $price_item = DB::table('page_setting_items')
            ->where('page_setting_id', $page_setting->id)
            ->where('type', PageSetting::ITEM_TYPE_FILTER)
            ->where('code', 'price')
            ->first();

        $this->assertNotNull($price_item);

        $price_item_get    = json_decode((string) $price_item->get, true, 512, JSON_THROW_ON_ERROR);
        $price_item_config = json_decode((string) $price_item->config, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('price', $price_item_get['key']);
        $this->assertSame('price_from', $price_item_get['extra']['from_key']);
        $this->assertSame('price_to', $price_item_get['extra']['to_key']);
        $this->assertSame(80.0, (float) $price_item_config['min_price']);
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
            $table->boolean('is_sorting_enabled')->default(true);
            $table->boolean('is_filtering_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('page_setting_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_setting_id');
            $table->unsignedBigInteger('language_id');
            $table->json('content')->nullable();
            $table->timestamps();
            $table->unique(['page_setting_id', 'language_id']);
        });

        Schema::create('page_setting_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_setting_id');
            $table->string('type', 20);
            $table->string('code', 120);
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('get');
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['page_setting_id', 'type', 'code']);
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

        Schema::create('product_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
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
