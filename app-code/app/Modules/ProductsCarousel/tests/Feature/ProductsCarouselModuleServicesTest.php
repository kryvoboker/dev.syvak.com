<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Tests\Feature;

use App\Models\Catalogs\Products\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsCarousel\Filament\ModuleInstanceFormSchema;
use Modules\ProductsCarousel\Services\Filament\ModuleSettingsNormalizerService;
use Modules\ProductsCarousel\Services\Filament\ProductsCarouselProductSearchService;
use Modules\ProductsCarousel\Services\Storefront\ProductsCarouselStorefrontService;
use Tests\TestCase;

class ProductsCarouselModuleServicesTest extends TestCase
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
        config()->set('page-settings.page_type', [
            'home' => 'home',
        ]);
        config()->set('app.currency.current_currency_code', 'UAH');
        config()->set('app.currency.default_exchange_rate', 1);
        config()->set('app.currency.current_exchange_rate', 1);

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
        $this->createCurrenciesTable();
        $this->createCategoriesTable();
        $this->createCategoryDescriptionsTable();
        $this->createProductsTable();
        $this->createProductDescriptionsTable();
        $this->createProductVariantsTable();
        $this->createProductVariantDescriptionsTable();
        $this->createProductVariantDiscountsTable();
        $this->createCategoryProductTable();
        $this->createSlugsTable();

        DB::table('languages')->insert([
            'id' => 1,
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
        ]);

        DB::table('currencies')->insert([
            'id' => 1,
            'code' => 'UAH',
            'name' => 'Hryvnia',
            'format_locale' => 'uk_UA',
            'symbol_left' => '',
            'symbol_right' => '₴',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_active' => true,
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
            'source_mode' => 'category_based',
            'category_based' => [
                'category_ids' => [1, 2],
                'use_selected_products_only' => true,
                'selected_product_ids' => [10, 11, 12],
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

    public function test_page_type_options_are_loaded_from_page_settings_configuration(): void
    {
        $schema = app(ModuleInstanceFormSchema::class);
        $reflection_method = new \ReflectionMethod($schema, 'getPageTypeOptions');
        $reflection_method->setAccessible(true);

        $this->assertSame(['home' => 'Home'], $reflection_method->invoke($schema));
    }

    public function test_normalizer_applies_products_filter_and_sort_defaults(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        $normalized_settings = $normalizer_service->normalize([
            'source_mode' => 'category_based',
            'shared' => [
                'page_types' => ['home'],
            ],
            'category_based' => [
                'category_ids' => [1],
                'use_selected_products_only' => false,
                'selected_product_ids' => [],
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
        $this->assertArrayHasKey('translations', $normalized_settings['shared']);
        $this->assertArrayHasKey('en', $normalized_settings['shared']['translations']);
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
                'shared' => [
                    'page_types' => ['home'],
                    'sort_mode' => 'custom',
                    'custom_sort_options' => ['name_asc', 'unknown_sort_option'],
                ],
                'category_based' => [
                    'category_ids' => [1],
                    'use_selected_products_only' => false,
                    'selected_product_ids' => [],
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
            'shared' => [
                'page_types' => ['home'],
                'sort_mode' => 'custom',
                'custom_sort' => [
                    'price' => 'asc',
                    'name' => 'none',
                    'date_added' => 'desc',
                    'quantity' => 'none',
                ],
                'custom_sort_options' => ['price_desc', 'price_asc', 'date_added_desc'],
            ],
            'category_based' => [
                'category_ids' => [1],
                'use_selected_products_only' => false,
                'selected_product_ids' => [],
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

    public function test_normalizer_maps_legacy_shared_scalars_to_all_active_language_translations(): void
    {
        DB::table('languages')->insert([
            'id' => 2,
            'code' => 'uk',
            'name' => 'Ukrainian',
            'is_active' => true,
            'is_default' => false,
        ]);

        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        $normalizer_service = app(ModuleSettingsNormalizerService::class);

        $normalized_settings = $normalizer_service->normalize([
            'source_mode' => 'category_based',
            'shared' => [
                'page_types' => ['home'],
                'module_name_for_user' => 'Legacy title',
                'short_description_for_user' => 'Legacy description',
            ],
            'category_based' => [
                'category_ids' => [1],
                'use_selected_products_only' => false,
                'selected_product_ids' => [],
            ],
            'manual_only' => [
                'selected_product_ids' => [],
            ],
        ]);

        $this->assertSame(
            'Legacy title',
            $normalized_settings['shared']['translations']['en']['module_name_for_user'],
        );
        $this->assertSame(
            'Legacy description',
            $normalized_settings['shared']['translations']['en']['short_description_for_user'],
        );
        $this->assertSame(
            'Legacy title',
            $normalized_settings['shared']['translations']['uk']['module_name_for_user'],
        );
        $this->assertSame(
            'Legacy description',
            $normalized_settings['shared']['translations']['uk']['short_description_for_user'],
        );
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

        DB::table('product_variants')->insert([
            ['id' => 1010, 'product_id' => 10, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 1020, 'product_id' => 20, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 1030, 'product_id' => 30, 'is_default' => false, 'is_active' => false, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
        ]);

        DB::table('category_product')->insert([
            ['category_id' => 1, 'product_id' => 10],
            ['category_id' => 2, 'product_id' => 20],
            ['category_id' => 1, 'product_id' => 30],
        ]);

        $search_service = app(ProductsCarouselProductSearchService::class);

        $results = $search_service->searchActiveByCategories('flow', [1]);

        $this->assertArrayHasKey(1010, $results);
        $this->assertArrayNotHasKey(1020, $results);
        $this->assertArrayNotHasKey(1030, $results);
    }

    public function test_category_scoped_search_excludes_already_selected_products(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'sort_order' => 1, 'is_active' => true],
        ]);

        DB::table('products')->insert([
            ['id' => 110, 'model' => 'FLOW-110', 'sku' => 'SKU-110', 'ean' => 110, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 120, 'model' => 'FLOW-120', 'sku' => 'SKU-120', 'ean' => 120, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('product_variants')->insert([
            ['id' => 1110, 'product_id' => 110, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 1120, 'product_id' => 120, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
        ]);

        DB::table('product_descriptions')->insert([
            ['product_id' => 110, 'language_id' => 1, 'name' => 'Flow Included', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 120, 'language_id' => 1, 'name' => 'Flow Excluded', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);

        DB::table('category_product')->insert([
            ['category_id' => 1, 'product_id' => 110],
            ['category_id' => 1, 'product_id' => 120],
        ]);

        $search_service = app(ProductsCarouselProductSearchService::class);

        $results = $search_service->searchActiveByCategories('flow', [1], [1120]);

        $this->assertArrayHasKey(1110, $results);
        $this->assertArrayNotHasKey(1120, $results);
    }

    public function test_global_search_excludes_already_selected_products(): void
    {
        DB::table('products')->insert([
            ['id' => 210, 'model' => 'GLOBAL-210', 'sku' => 'SKU-210', 'ean' => 210, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 220, 'model' => 'GLOBAL-220', 'sku' => 'SKU-220', 'ean' => 220, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
            ['id' => 230, 'model' => 'GLOBAL-230', 'sku' => 'SKU-230', 'ean' => 230, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => false, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('product_variants')->insert([
            ['id' => 1210, 'product_id' => 210, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 1220, 'product_id' => 220, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 1230, 'product_id' => 230, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
        ]);

        DB::table('product_descriptions')->insert([
            ['product_id' => 210, 'language_id' => 1, 'name' => 'Global Included', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 220, 'language_id' => 1, 'name' => 'Global Excluded', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
            ['product_id' => 230, 'language_id' => 1, 'name' => 'Global Inactive', 'description' => 'd', 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);

        $search_service = app(ProductsCarouselProductSearchService::class);

        $results = $search_service->searchAllActive('global', [1220]);

        $this->assertArrayHasKey(1210, $results);
        $this->assertArrayNotHasKey(1220, $results);
        $this->assertArrayNotHasKey(1230, $results);
    }

    public function test_variant_search_matches_product_fields_and_regular_or_discount_price(): void
    {
        DB::table('products')->insert([
            ['id' => 300, 'model' => 'VARIANT-MODEL', 'sku' => 'VARIANT-SKU', 'ean' => 987654, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
        ]);

        DB::table('product_descriptions')->insert([
            ['product_id' => 300, 'language_id' => 1, 'name' => 'Variant Search Product', 'description' => null, 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);

        DB::table('product_variants')->insert([
            ['id' => 301, 'product_id' => 300, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 302, 'product_id' => 300, 'is_default' => false, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 200, 'sort_order' => 2],
            ['id' => 303, 'product_id' => 300, 'is_default' => false, 'is_active' => false, 'quantity' => 5, 'minimum' => 1, 'price' => 300, 'sort_order' => 3],
        ]);

        DB::table('product_variant_discounts')->insert([
            ['id' => 1, 'product_variant_id' => 302, 'user_group_id' => null, 'quantity' => 1, 'priority' => 1, 'price' => 150, 'date_start' => now()->subDay(), 'date_end' => now()->addDay()],
        ]);

        $search_service = app(ProductsCarouselProductSearchService::class);

        $by_ean = $search_service->searchAllActive('987654');
        $by_regular_price = $search_service->searchAllActive('VARIANT-SKU == 200');
        $by_discount_price = $search_service->searchAllActive('Variant Search == 150');
        $inactive_variant = $search_service->searchAllActive('Variant Search == 300');

        $this->assertArrayHasKey(301, $by_ean);
        $this->assertArrayHasKey(302, $by_ean);
        $this->assertArrayHasKey(302, $by_regular_price);
        $this->assertArrayHasKey(302, $by_discount_price);
        $this->assertArrayNotHasKey(303, $inactive_variant);
        $this->assertStringContainsString('Variant 1 (default)', $by_ean[301]);
        $this->assertStringContainsString('Variant 2', $by_ean[302]);
        $this->assertStringContainsString('discount:', $by_ean[302]);
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

        $module_data_service = app(ProductsCarouselStorefrontService::class);

        $reflection_method = new \ReflectionMethod($module_data_service, 'resolveManualOnlyProducts');
        $reflection_method->setAccessible(true);

        /** @var EloquentCollection<int, Product> $products */
        $products = $reflection_method->invoke(
            $module_data_service,
            ['manual_only' => ['selected_product_ids' => [10, 20, 30]]],
            [
                'min_quantity' => 3,
                'products_limit' => 1,
                'product_image_width' => 300,
                'product_image_height' => 300,
                'sort_mode' => 'custom',
                'sort_sequence' => ['date_added_desc'],
            ],
        );

        $this->assertCount(1, $products);
        $product = $products->first();

        $this->assertNotNull($product);
        $this->assertSame(20, (int) $product->id);
    }

    public function test_storefront_card_uses_discount_from_selected_variant(): void
    {
        DB::table('products')->insert([
            ['id' => 400, 'model' => 'M-400', 'sku' => 'SKU-400', 'ean' => 400, 'quantity' => 5, 'minimum' => 1, 'image' => null, 'price' => 100, 'viewed' => 0, 'is_active' => true, 'date_available' => now(), 'date_added' => now()],
        ]);
        DB::table('product_descriptions')->insert([
            ['product_id' => 400, 'language_id' => 1, 'name' => 'Selected variant product', 'description' => null, 'h1_title' => null, 'meta_title' => null, 'meta_description' => null, 'meta_keywords' => null],
        ]);
        DB::table('product_variants')->insert([
            ['id' => 401, 'product_id' => 400, 'is_default' => true, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 100, 'sort_order' => 1],
            ['id' => 402, 'product_id' => 400, 'is_default' => false, 'is_active' => true, 'quantity' => 5, 'minimum' => 1, 'price' => 200, 'sort_order' => 2],
        ]);
        DB::table('product_variant_discounts')->insert([
            ['id' => 10, 'product_variant_id' => 402, 'user_group_id' => null, 'quantity' => 1, 'priority' => 1, 'price' => 150, 'date_start' => now()->subDay(), 'date_end' => now()->addDay()],
        ]);

        $product = Product::query()
            ->with([
                'productDescription' => fn ($query) => $query->where('language_id', 1),
                'slugs' => fn ($query) => $query->where('language_id', 1),
                'variants' => function ($query): void {
                    $query
                        ->where('is_active', true)
                        ->with([
                            'descriptions' => fn ($description_query) => $description_query->where('language_id', 1),
                            'slugs' => fn ($slug_query) => $slug_query->where('language_id', 1),
                            'discounts' => fn ($discount_query) => $discount_query
                                ->whereNull('user_group_id')
                                ->where('date_start', '<=', now())
                                ->where('date_end', '>=', now()),
                        ]);
                },
            ])
            ->findOrFail(400);

        $selected_variant = $product->variants->firstWhere('id', 402);
        $service = app(ProductsCarouselStorefrontService::class);
        $reflection_method = new \ReflectionMethod($service, 'mapProductCard');
        $reflection_method->setAccessible(true);

        /** @var array<string, mixed> $card */
        $card = $reflection_method->invoke($service, $product, 300, 300, $selected_variant);

        $this->assertSame(format_price(150, 'UAH', 1), $card['price']);
        $this->assertSame(format_price(200, 'UAH', 1), $card['rrc_price']);
        $this->assertTrue($card['is_discounted']);
        $this->assertSame(402, $card['variant_id']);
    }

    public function test_storefront_products_carousel_card_contains_variant_id_for_cart_addition(): void
    {
        $html = view('productscarousel::storefront.products-carousel', [
            'products_carousel_module_data' => [
                'instance_id' => 1,
                'module_name_for_user' => 'Products',
                'short_description_for_user' => '',
                'page_types' => [],
                'products' => [
                    [
                        'variant_id' => 42,
                        'name' => 'Test product',
                        'price' => '100 UAH',
                        'rrc_price' => '',
                        'is_discounted' => false,
                        'url' => '#',
                        'image_data' => [
                            'urls' => [
                                'original_thumb' => '',
                                'thumb_1x' => '',
                            ],
                            'width' => 420,
                            'height' => 420,
                        ],
                    ],
                ],
            ],
            'page_type' => 'home',
        ])->render();

        $this->assertStringContainsString('data-add-to-cart="42"', $html);
    }

    public function test_runtime_resolver_uses_current_locale_shared_translation_with_fallback(): void
    {
        DB::table('languages')->insert([
            'id' => 2,
            'code' => 'uk',
            'name' => 'Ukrainian',
            'is_active' => true,
            'is_default' => false,
        ]);

        $module_data_service = app(ProductsCarouselStorefrontService::class);
        $reflection_method = new \ReflectionMethod($module_data_service, 'resolveLocalizedSharedContent');
        $reflection_method->setAccessible(true);

        app()->setLocale('uk');

        /** @var array<string, mixed> $localized_shared */
        $localized_shared = $reflection_method->invoke(
            $module_data_service,
            [
                'shared' => [
                    'translations' => [
                        'en' => [
                            'module_name_for_user' => 'English title',
                            'short_description_for_user' => 'English description',
                        ],
                        'uk' => [
                            'module_name_for_user' => 'Український заголовок',
                            'short_description_for_user' => 'Український опис',
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame('Український заголовок', $localized_shared['module_name_for_user']);
        $this->assertSame('Український опис', $localized_shared['short_description_for_user']);
        $this->assertSame('uk', $localized_shared['requested_locale']);
        $this->assertSame('uk', $localized_shared['resolved_locale']);
        $this->assertFalse((bool) $localized_shared['fallback_used']);

        app()->setLocale('uk');

        /** @var array<string, mixed> $localized_shared_with_fallback */
        $localized_shared_with_fallback = $reflection_method->invoke(
            $module_data_service,
            [
                'shared' => [
                    'translations' => [
                        'en' => [
                            'module_name_for_user' => 'English title',
                            'short_description_for_user' => 'English description',
                        ],
                        'uk' => [
                            'module_name_for_user' => '',
                            'short_description_for_user' => '',
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame('English title', $localized_shared_with_fallback['module_name_for_user']);
        $this->assertSame('English description', $localized_shared_with_fallback['short_description_for_user']);
        $this->assertSame('uk', $localized_shared_with_fallback['requested_locale']);
        $this->assertSame('en', $localized_shared_with_fallback['resolved_locale']);
        $this->assertTrue((bool) $localized_shared_with_fallback['fallback_used']);
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

    private function createCurrenciesTable(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10);
            $table->string('name');
            $table->string('format_locale')->nullable();
            $table->string('symbol_left')->nullable();
            $table->string('symbol_right')->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
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

    private function createProductVariantsTable(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->string('image')->nullable();
            $table->dateTime('date_available')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function createProductVariantDescriptionsTable(): void
    {
        Schema::create('product_variant_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
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

    private function createProductVariantDiscountsTable(): void
    {
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
