<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ProductsCarousel;

use App\Models\Catalogs\Products\Product;
use App\Models\Modules\ModuleInstance;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsCarousel\Services\ModuleSettingsNormalizerService;
use Modules\ProductsCarousel\Services\ProductsCarouselModuleDataService;
use Modules\ProductsCarousel\Services\ProductsCarouselProductSearchService;
use Tests\TestCase;

class ProductsCarouselModuleServicesTest extends TestCase
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
        DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function (mixed ...$args): int {
            $needle = array_shift($args);

            if ($needle === null) {
                return 0;
            }

            $position = array_search($needle, $args, true);

            return $position === false ? 0 : ((int) $position + 1);
        }, -1);

        $this->createLanguagesTable();
        $this->createCategoriesTable();
        $this->createCategoryDescriptionsTable();
        $this->createProductsTable();
        $this->createProductDescriptionsTable();
        $this->createCategoryProductTable();
        $this->createSlugsTable();

        DB::table('languages')->insert([
            'id'         => 1,
            'code'       => 'en',
            'name'       => 'English',
            'is_active'  => true,
            'is_default' => true,
        ]);

        app()->setLocale('en');
    }

    public function test_normalizer_filters_inactive_categories_and_products(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
            ['id' => 2, 'parent_id' => null, 'sort_order' => 2, 'is_active' => false],
        ]);

        DB::table('products')->insert([
            ['id' => 10, 'model' => 'M-10', 'sku' => 'SKU-10', 'ean' => 10, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 11, 'model' => 'M-11', 'sku' => 'SKU-11', 'ean' => 11, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => false, 'date_available' => now(), 'date_added' => now()],
            ['id' => 12, 'model' => 'M-12', 'sku' => 'SKU-12', 'ean' => 12, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('category_product')->insert([
            ['category_id' => 1, 'product_id' => 10],
            ['category_id' => 1, 'product_id' => 11],
            ['category_id' => 2, 'product_id' => 12],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        $normalized_settings = $normalizer_service->normalize([
            'source_mode'    => 'category_based',
            'category_based' => [
                'category_ids'               => [1, 2],
                'use_selected_products_only' => true,
                'selected_product_ids'       => [10, 11, 12],
            ],
            'manual_only' => [
                'selected_product_ids' => [10, 11, 12],
            ],
        ]);

        $this->assertSame('category_based', $normalized_settings['source_mode']);
        $this->assertSame([1], $normalized_settings['category_based']['category_ids']);
        $this->assertSame([10], $normalized_settings['category_based']['selected_product_ids']);
        $this->assertSame([10, 12], $normalized_settings['manual_only']['selected_product_ids']);
    }

    public function test_normalizer_applies_products_filter_and_sort_defaults(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        $normalized_settings = $normalizer_service->normalize([
            'source_mode' => 'category_based',
            'shared'      => [
                'page_types' => ['home'],
            ],
            'category_based' => [
                'category_ids'               => [1],
                'use_selected_products_only' => false,
                'selected_product_ids'       => [],
            ],
            'manual_only' => [
                'selected_product_ids' => [],
            ],
        ]);

        $this->assertSame(1, $normalized_settings['shared']['min_quantity']);
        $this->assertSame(15, $normalized_settings['shared']['products_limit']);
        $this->assertSame(420, $normalized_settings['shared']['product_image_width']);
        $this->assertSame(420, $normalized_settings['shared']['product_image_height']);
        $this->assertSame('custom', $normalized_settings['shared']['sort_mode']);
        $this->assertSame([], $normalized_settings['shared']['custom_sort_options']);
    }

    public function test_normalizer_rejects_invalid_custom_sort_options(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        try {
            $normalizer_service->normalize([
                'source_mode' => 'category_based',
                'shared'      => [
                    'page_types'          => ['home'],
                    'sort_mode'           => 'custom',
                    'custom_sort_options' => ['name_asc', 'unknown_sort_option'],
                ],
                'category_based' => [
                    'category_ids'               => [1],
                    'use_selected_products_only' => false,
                    'selected_product_ids'       => [],
                ],
                'manual_only' => [
                    'selected_product_ids' => [],
                ],
            ]);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $validation_exception) {
            $this->assertArrayHasKey(
                'settings.shared.custom_sort_options',
                $validation_exception->errors(),
            );
        }
    }

    public function test_normalizer_builds_custom_sort_options_from_radio_directions(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        $normalized_settings = $normalizer_service->normalize([
            'source_mode' => 'category_based',
            'shared'      => [
                'page_types'  => ['home'],
                'sort_mode'   => 'custom',
                'custom_sort' => [
                    'price'      => 'asc',
                    'name'       => 'none',
                    'date_added' => 'desc',
                    'quantity'   => 'none',
                ],
                'custom_sort_options' => ['price_desc', 'price_asc', 'date_added_desc'],
            ],
            'category_based' => [
                'category_ids'               => [1],
                'use_selected_products_only' => false,
                'selected_product_ids'       => [],
            ],
            'manual_only' => [
                'selected_product_ids' => [],
            ],
        ]);

        $this->assertSame(
            ['price_asc', 'date_added_desc'],
            $normalized_settings['shared']['custom_sort_options'],
        );
        $this->assertSame('asc', $normalized_settings['shared']['custom_sort']['price']);
        $this->assertSame('desc', $normalized_settings['shared']['custom_sort']['date_added']);
    }

    public function test_category_scoped_search_returns_only_active_products_from_selected_categories(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
            ['id' => 2, 'parent_id' => null, 'sort_order' => 2, 'is_active' => true],
        ]);

        DB::table('products')->insert([
            ['id' => 10, 'model' => 'FLOW-10', 'sku' => 'SKU-10', 'ean' => 10, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 20, 'model' => 'FLOW-20', 'sku' => 'SKU-20', 'ean' => 20, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 30, 'model' => 'FLOW-30', 'sku' => 'SKU-30', 'ean' => 30, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => false, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('product_descriptions')->insert([
            ['product_id' => 10, 'language_id' => 1, 'name' => 'Flow Product One', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 20, 'language_id' => 1, 'name' => 'Flow Product Two', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 30, 'language_id' => 1, 'name' => 'Flow Product Three', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);

        DB::table('category_product')->insert([
            ['category_id' => 1, 'product_id' => 10],
            ['category_id' => 2, 'product_id' => 20],
            ['category_id' => 1, 'product_id' => 30],
        ]);

        $search_service = app(ProductsCarouselProductSearchService::class);

        $results = $search_service->searchActiveByCategories('flow', [1]);

        $this->assertArrayHasKey(10, $results);
        $this->assertArrayNotHasKey(20, $results);
        $this->assertArrayNotHasKey(30, $results);
    }

    public function test_runtime_resolver_applies_min_quantity_and_products_limit_for_manual_mode(): void
    {
        DB::table('products')->insert([
            ['id' => 10, 'model' => 'M-10', 'sku' => 'SKU-10', 'ean' => 10, 'quantity' => 1, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 20, 'model' => 'M-20', 'sku' => 'SKU-20', 'ean' => 20, 'quantity' => 3, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 30, 'model' => 'M-30', 'sku' => 'SKU-30', 'ean' => 30, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('product_descriptions')->insert([
            ['product_id' => 10, 'language_id' => 1, 'name' => 'Product 10', 'description' => null, 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 20, 'language_id' => 1, 'name' => 'Product 20', 'description' => null, 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 30, 'language_id' => 1, 'name' => 'Product 30', 'description' => null, 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);

        $module_data_service = app(ProductsCarouselModuleDataService::class);
        $module_instance     = new ModuleInstance([
            'id' => 501,
        ]);

        $reflection_method = new \ReflectionMethod($module_data_service, 'resolveManualOnlyProducts');
        $reflection_method->setAccessible(true);

        /** @var EloquentCollection<int, Product> $products */
        $products = $reflection_method->invoke(
            $module_data_service,
            $module_instance,
            ['manual_only' => ['selected_product_ids' => [10, 20, 30]]],
            [
                'min_quantity'         => 3,
                'products_limit'       => 1,
                'product_image_width'  => 300,
                'product_image_height' => 300,
                'sort_mode'            => 'custom',
                'sort_sequence'        => ['date_added_desc'],
            ],
        );

        $this->assertCount(1, $products);
        $this->assertSame(20, (int) $products->first()->id);
    }

    private function createLanguagesTable(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    private function createCategoriesTable(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createCategoryDescriptionsTable(): void
    {
        Schema::create('category_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('language_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    private function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('model')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('ean')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum')->default(1);
            $table->string('image')->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('date_available')->nullable();
            $table->dateTime('date_added')->nullable();
            $table->timestamps();
        });
    }

    private function createProductDescriptionsTable(): void
    {
        Schema::create('product_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('language_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('h1_title')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    private function createCategoryProductTable(): void
    {
        Schema::create('category_product', function (Blueprint $table): void {
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });
    }

    private function createSlugsTable(): void
    {
        Schema::create('slugs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sluggable_id');
            $table->string('sluggable_type');
            $table->unsignedBigInteger('language_id');
            $table->string('slug', 500);
            $table->timestamps();
        });
    }
}
