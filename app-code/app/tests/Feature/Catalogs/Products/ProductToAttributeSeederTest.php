<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use Database\Seeders\Catalogs\Products\ProductToAttributeSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductToAttributeSeederTest extends TestCase
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
        $this->createAttributesTable();
        $this->createAttributeDescriptionsTable();
        $this->createProductsTable();
        $this->createProductToAttributesTable();
    }

    public function test_it_seeds_supported_attribute_ids_with_repeated_values_for_active_languages(): void
    {
        $seed_payload = $this->prepareSourceDataForSeeder();

        $this->seed(ProductToAttributeSeeder::class);

        $rows_count = DB::table('product_to_attributes')->count();

        $expected_rows_count = $seed_payload['products_count']
            * $seed_payload['attributes_count']
            * $seed_payload['languages_count'];

        $this->assertSame($expected_rows_count, $rows_count);

        $unique_rows_count = DB::query()
            ->fromSub(
                DB::table('product_to_attributes')
                    ->select(['product_id', 'attribute_id', 'language_id'])
                    ->groupBy(['product_id', 'attribute_id', 'language_id']),
                'unique_product_attributes',
            )
            ->count();

        $this->assertSame($rows_count, $unique_rows_count);

        $repeated_values_count = DB::query()
            ->fromSub(
                DB::table('product_to_attributes')
                    ->select('text')
                    ->where('attribute_id', $seed_payload['size_attribute_id'])
                    ->where('language_id', $seed_payload['uk_language_id'])
                    ->groupBy('text')
                    ->havingRaw('COUNT(*) >= 2'),
                'repeated_values',
            )
            ->count();

        $this->assertGreaterThan(0, $repeated_values_count);
    }

    public function test_it_ignores_attributes_outside_supported_attribute_ids(): void
    {
        $this->prepareSourceDataForSeeder();

        DB::table('attributes')->insert([
            'id'         => 99,
            'sort_order' => 99,
            'is_active'  => true,
            'created_at' => now(config('app.timezone')),
            'updated_at' => now(config('app.timezone')),
        ]);

        $this->seed(ProductToAttributeSeeder::class);

        $this->assertDatabaseMissing('product_to_attributes', [
            'attribute_id' => 99,
        ]);
    }

    public function test_it_is_idempotent_when_seeder_runs_multiple_times(): void
    {
        $seed_payload = $this->prepareSourceDataForSeeder();

        $this->seed(ProductToAttributeSeeder::class);

        $first_run_rows_count = DB::table('product_to_attributes')->count();

        $this->seed(ProductToAttributeSeeder::class);

        $second_run_rows_count = DB::table('product_to_attributes')->count();

        $expected_rows_count = $seed_payload['products_count']
            * $seed_payload['attributes_count']
            * $seed_payload['languages_count'];

        $this->assertSame($expected_rows_count, $first_run_rows_count);
        $this->assertSame($first_run_rows_count, $second_run_rows_count);
    }

    /**
     * @return array<string, int>
     */
    private function prepareSourceDataForSeeder(): array
    {
        $now_timestamp = now(config('app.timezone'));

        DB::table('languages')->insert([
            [
                'code'       => 'uk',
                'name'       => 'Ukrainian',
                'is_active'  => true,
                'is_default' => true,
                'created_at' => $now_timestamp,
                'updated_at' => $now_timestamp,
            ],
            [
                'code'       => 'en',
                'name'       => 'English',
                'is_active'  => true,
                'is_default' => false,
                'created_at' => $now_timestamp,
                'updated_at' => $now_timestamp,
            ],
        ]);

        $languages_by_code = DB::table('languages')
            ->whereIn('code', ['uk', 'en'])
            ->pluck('id', 'code');

        DB::table('attributes')->insert([
            [
                'id'         => 6,
                'sort_order' => 1,
                'is_active'  => true,
                'created_at' => $now_timestamp,
                'updated_at' => $now_timestamp,
            ],
            [
                'id'         => 8,
                'sort_order' => 2,
                'is_active'  => true,
                'created_at' => $now_timestamp,
                'updated_at' => $now_timestamp,
            ],
        ]);

        DB::table('attribute_descriptions')->insert([
            [
                'attribute_id' => 6,
                'language_id'  => (int) $languages_by_code['uk'],
                'name'         => 'Розмір',
                'created_at'   => $now_timestamp,
                'updated_at'   => $now_timestamp,
            ],
            [
                'attribute_id' => 6,
                'language_id'  => (int) $languages_by_code['en'],
                'name'         => 'Size',
                'created_at'   => $now_timestamp,
                'updated_at'   => $now_timestamp,
            ],
            [
                'attribute_id' => 8,
                'language_id'  => (int) $languages_by_code['uk'],
                'name'         => 'Колір',
                'created_at'   => $now_timestamp,
                'updated_at'   => $now_timestamp,
            ],
            [
                'attribute_id' => 8,
                'language_id'  => (int) $languages_by_code['en'],
                'name'         => 'Color',
                'created_at'   => $now_timestamp,
                'updated_at'   => $now_timestamp,
            ],
        ]);

        $products = [];

        for ($product_index = 1; $product_index <= 8; $product_index++) {
            $products[] = [
                'model'          => 'MODEL-' . $product_index,
                'sku'            => 'SKU-' . $product_index,
                'ean'            => 'EAN-' . $product_index,
                'quantity'       => 10,
                'minimum'        => 1,
                'image'          => null,
                'price'          => 199.99,
                'viewed'         => 0,
                'is_active'      => true,
                'date_available' => $now_timestamp,
                'date_added'     => $now_timestamp,
                'created_at'     => $now_timestamp,
                'updated_at'     => $now_timestamp,
            ];
        }

        DB::table('products')->insert($products);

        return [
            'products_count'    => 8,
            'attributes_count'  => 2,
            'languages_count'   => 2,
            'size_attribute_id' => 6,
            'uk_language_id'    => (int) $languages_by_code['uk'],
        ];
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

    private function createAttributesTable(): void
    {
        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createAttributeDescriptionsTable(): void
    {
        Schema::create('attribute_descriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->unique(['attribute_id', 'language_id']);
        });
    }

    private function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('model')->unique()->nullable();
            $table->string('sku')->nullable();
            $table->string('ean')->unique()->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum')->default(1);
            $table->string('image', 3000)->nullable();
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('is_active')->default(false);
            $table->dateTime('date_available')->nullable();
            $table->dateTime('date_added')->nullable();
            $table->timestamps();
        });
    }

    private function createProductToAttributesTable(): void
    {
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
