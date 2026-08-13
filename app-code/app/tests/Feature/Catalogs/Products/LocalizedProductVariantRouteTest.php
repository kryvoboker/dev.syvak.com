<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use App\Http\Controllers\Pages\ProductController;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Slug;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class LocalizedProductVariantRouteTest extends TestCase
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
        $this->createProductsTable();
        $this->createProductVariantsTable();
        $this->createProductVariantAttributeValuesTable();
        $this->createSlugsTable();
    }

    public function test_it_returns_variant_seo_url_even_when_filter_value_id_is_from_other_locale(): void
    {
        $this->seedLanguages();

        $product = Product::query()->create([
            'model' => 'MODEL-840',
            'sku' => 'SKU-840',
            'ean' => 'EAN-840',
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'is_active' => true,
            'date_available' => now(config('app.timezone')),
            'date_added' => now(config('app.timezone')),
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'is_default' => true,
            'is_active' => true,
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'sort_order' => 1,
        ]);

        $uk_attribute_value = DB::table('product_variant_attribute_values')->insertGetId([
            'product_variant_id' => (int) $variant->id,
            'attribute_id' => 5,
            'language_id' => 1,
            'value_string' => '20x20 см',
            'created_at' => now(config('app.timezone')),
            'updated_at' => now(config('app.timezone')),
        ]);

        DB::table('product_variant_attribute_values')->insert([
            'product_variant_id' => (int) $variant->id,
            'attribute_id' => 5,
            'language_id' => 2,
            'value_string' => '20x20 cm',
            'created_at' => now(config('app.timezone')),
            'updated_at' => now(config('app.timezone')),
        ]);

        Slug::query()->create([
            'sluggable_type' => ProductVariant::class,
            'sluggable_id' => (int) $variant->id,
            'language_id' => 1,
            'slug' => 'bereginya-sadu',
        ]);

        Slug::query()->create([
            'sluggable_type' => ProductVariant::class,
            'sluggable_id' => (int) $variant->id,
            'language_id' => 2,
            'slug' => 'garden-guardian',
        ]);

        app()->setLocale('en');

        $generated_url = localized_product_variant_route(
            product_slug: 'bereginya-sadu',
            product_id: (int) $product->id,
            attribute_filters: ['attribute_5' => (string) $uk_attribute_value],
        );

        $this->assertStringContainsString('/en/product/bereginya-sadu/garden-guardian', $generated_url);
    }

    public function test_it_returns_not_found_for_an_unknown_explicit_variant_slug(): void
    {
        $this->seedLanguages();

        $product = Product::query()->create([
            'model' => 'MODEL-844',
            'sku' => 'SKU-844',
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'is_active' => true,
        ]);

        Slug::query()->create([
            'sluggable_type' => Product::class,
            'sluggable_id' => (int) $product->id,
            'language_id' => 1,
            'slug' => 'product-uk',
        ]);

        $this->expectException(NotFoundHttpException::class);

        app(ProductController::class)->show(
            Request::create('/uk/product/product-uk/fdsf'),
            'uk',
            'product-uk',
            'fdsf',
        );
    }

    public function test_it_preserves_sparse_numeric_attribute_ids_when_resolving_variant_route(): void
    {
        $this->seedLanguages();

        $product = Product::query()->create([
            'model' => 'MODEL-841',
            'sku' => 'SKU-841',
            'ean' => 'EAN-841',
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'is_active' => true,
            'date_available' => now(config('app.timezone')),
            'date_added' => now(config('app.timezone')),
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'is_default' => true,
            'is_active' => true,
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'sort_order' => 1,
        ]);

        $attribute_value_id_five = DB::table('product_variant_attribute_values')->insertGetId([
            'product_variant_id' => (int) $variant->id,
            'attribute_id' => 5,
            'language_id' => 1,
            'value_string' => '20x20 см',
            'created_at' => now(config('app.timezone')),
            'updated_at' => now(config('app.timezone')),
        ]);

        $attribute_value_id_seven = DB::table('product_variant_attribute_values')->insertGetId([
            'product_variant_id' => (int) $variant->id,
            'attribute_id' => 7,
            'language_id' => 1,
            'value_string' => 'Чорний',
            'created_at' => now(config('app.timezone')),
            'updated_at' => now(config('app.timezone')),
        ]);

        Slug::query()->create([
            'sluggable_type' => ProductVariant::class,
            'sluggable_id' => (int) $variant->id,
            'language_id' => 1,
            'slug' => 'bereginya-sadu',
        ]);

        Slug::query()->create([
            'sluggable_type' => ProductVariant::class,
            'sluggable_id' => (int) $variant->id,
            'language_id' => 2,
            'slug' => 'garden-guardian',
        ]);

        app()->setLocale('en');

        $generated_url = localized_product_variant_route(
            product_slug: 'bereginya-sadu',
            product_id: (int) $product->id,
            attribute_filters: [
                5 => (string) $attribute_value_id_five,
                7 => (string) $attribute_value_id_seven,
            ],
        );

        $this->assertStringContainsString('/en/product/bereginya-sadu/garden-guardian', $generated_url);
    }

    public function test_it_uses_the_localized_product_slug_for_a_default_variant_without_its_own_slug(): void
    {
        $this->seedLanguages();

        $product = Product::query()->create([
            'model' => 'MODEL-842',
            'sku' => 'SKU-842',
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'is_active' => true,
        ]);

        ProductVariant::query()->create([
            'product_id' => (int)$product->id,
            'is_default' => true,
            'is_active' => true,
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
        ]);

        Slug::query()->insert([
            [
                'sluggable_type' => Product::class,
                'sluggable_id' => (int)$product->id,
                'language_id' => 1,
                'slug' => 'product-uk',
            ],
            [
                'sluggable_type' => Product::class,
                'sluggable_id' => (int)$product->id,
                'language_id' => 2,
                'slug' => 'product-en',
            ],
        ]);

        app()->setLocale('uk');

        $slug_variants = get_slug_variants(
            sluggable_type: ProductVariant::class,
            slug_value: 'product-uk',
        );

        $this->assertSame(['slug' => 'product-en'], $slug_variants['en']);
    }

    public function test_it_uses_a_static_variant_url_when_variant_seo_slug_is_missing(): void
    {
        $this->seedLanguages();

        $product = Product::query()->create([
            'model' => 'MODEL-843',
            'sku' => 'SKU-843',
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
            'is_active' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => (int)$product->id,
            'is_default' => false,
            'is_active' => true,
            'quantity' => 10,
            'minimum' => 1,
            'price' => 1000,
        ]);

        Slug::query()->insert([
            [
                'sluggable_type' => Product::class,
                'sluggable_id' => (int)$product->id,
                'language_id' => 1,
                'slug' => 'product-uk',
            ],
            [
                'sluggable_type' => Product::class,
                'sluggable_id' => (int)$product->id,
                'language_id' => 2,
                'slug' => 'product-en',
            ],
            [
                'sluggable_type' => ProductVariant::class,
                'sluggable_id' => (int)$variant->id,
                'language_id' => 1,
                'slug' => 'variant-uk',
            ],
        ]);

        app()->setLocale('uk');

        $slug_variants = get_slug_variants(
            sluggable_type: ProductVariant::class,
            slug_value: 'product-uk',
            variant_slug_value: 'variant-uk',
        );

        $this->assertSame([
            'route' => 'localized.catalog.product.static.show',
            'product_id' => (int)$product->id,
            'variant_id' => (int)$variant->id,
        ], $slug_variants['en']);
    }

    private function seedLanguages(): void
    {
        DB::table('languages')->insert([
            [
                'id' => 1,
                'code' => 'uk',
                'name' => 'Ukrainian',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(config('app.timezone')),
                'updated_at' => now(config('app.timezone')),
            ],
            [
                'id' => 2,
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(config('app.timezone')),
                'updated_at' => now(config('app.timezone')),
            ],
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

    private function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('default_variant_id')->nullable();
            $table->unsignedBigInteger('default_category_id')->nullable();
            $table->string('model', 255);
            $table->string('sku', 255);
            $table->string('ean', 255)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->string('image', 3000)->nullable();
            $table->double('price')->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('date_available')->nullable();
            $table->timestamp('date_added')->nullable();
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
            $table->double('price')->default(0);
            $table->string('image', 3000)->nullable();
            $table->timestamp('date_available')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function createProductVariantAttributeValuesTable(): void
    {
        Schema::create('product_variant_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('value_string', 3000)->nullable();
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
            $table->string('slug');
            $table->timestamps();
        });
    }
}
