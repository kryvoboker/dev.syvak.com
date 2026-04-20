<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductVariantDefaultUniquenessTest extends TestCase
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

        $this->createProductsTable();
        $this->createProductVariantsTable();
    }

    public function test_only_one_variant_can_remain_default_for_the_same_product(): void
    {
        $product = Product::query()->create([
            'model'          => 'MODEL-DEFAULT-001',
            'sku'            => 'SKU-DEFAULT-001',
            'ean'            => '2999999990011',
            'quantity'       => 10,
            'minimum'        => 1,
            'price'          => 1000,
            'viewed'         => 0,
            'is_active'      => true,
            'date_available' => now(config('app.timezone')),
            'date_added'     => now(config('app.timezone')),
        ]);

        $first_default_variant = ProductVariant::query()->create([
            'product_id'      => (int) $product->id,
            'is_default'      => true,
            'is_active'       => true,
            'quantity'        => 10,
            'minimum'         => 1,
            'price'           => 1000,
            'sort_order'      => 1,
            'size_guide_data' => null,
        ]);

        $second_default_variant = ProductVariant::query()->create([
            'product_id'      => (int) $product->id,
            'is_default'      => true,
            'is_active'       => true,
            'quantity'        => 10,
            'minimum'         => 1,
            'price'           => 1200,
            'sort_order'      => 2,
            'size_guide_data' => null,
        ]);

        $this->assertFalse((bool) $first_default_variant->fresh()?->is_default);
        $this->assertTrue((bool) $second_default_variant->fresh()?->is_default);
        $this->assertSame((int) $second_default_variant->id, (int) $product->fresh()?->default_variant_id);
        $this->assertSame(
            1,
            ProductVariant::query()
                ->where('product_id', (int) $product->id)
                ->where('is_default', true)
                ->count(),
        );
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
            $table->json('size_guide_data')->nullable();
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
            $table->json('size_guide_data')->nullable();
            $table->timestamps();
        });
    }
}
