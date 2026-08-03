<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogs\Products;

use App\Models\Catalogs\Products\ProductVariant;
use App\Services\Catalogs\Products\ProductVariantContentPersistenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductVariantContentPersistenceServiceTest extends TestCase
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
        Schema::disableForeignKeyConstraints();

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('minimum')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->timestamps();
        });

        foreach (['product_variant_size_guides', 'product_variant_compositions', 'product_variant_cares'] as $table_name) {
            Schema::create($table_name, function (Blueprint $table) use ($table_name): void {
                $table->id();
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('language_id')->nullable();

                if ($table_name === 'product_variant_size_guides') {
                    $table->string('short_title')->nullable();
                    $table->string('short_description', 1000)->nullable();
                    $table->text('table_rows')->nullable();
                    $table->string('image')->nullable();
                    $table->unsignedInteger('image_width')->nullable();
                    $table->unsignedInteger('image_height')->nullable();
                    $table->string('full_description_title')->nullable();
                    $table->text('full_description')->nullable();
                } else {
                    $table->string('title')->nullable();
                    $table->json('items')->nullable();
                }

                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function test_it_persists_and_hydrates_variant_content(): void
    {
        $variant = ProductVariant::query()->create([
            'product_id' => 1,
            'is_default' => false,
            'is_active' => true,
            'quantity' => 1,
            'minimum' => 1,
            'price' => 100,
        ]);

        $service = app(ProductVariantContentPersistenceService::class);
        $service->syncRelations($variant, [
            'size_guide_data' => [
                'translations' => [
                    '1' => [
                        'title' => 'Size guide',
                        'table_rows' => "Size|Chest\nM|100",
                        'image_width' => '400',
                        'image_height' => '500',
                    ],
                ],
            ],
            'composition_and_care_data' => [
                'translations' => [
                    '1' => [
                        'composition' => [
                            'title' => 'Composition',
                            'items' => [['value' => 'Cotton']],
                        ],
                        'care' => [
                            'title' => 'Care',
                            'items' => [['value' => 'Wash at 30°C']],
                        ],
                    ],
                ],
            ],
        ]);

        $hydrated_data = $service->hydrateFormData($variant->fresh());

        $this->assertSame('Size guide', data_get($hydrated_data, 'size_guide_data.translations.1.title'));
        $this->assertSame(400, data_get($hydrated_data, 'size_guide_data.translations.1.image_width'));
        $this->assertSame('Cotton', data_get($hydrated_data, 'composition_and_care_data.translations.1.composition.items.0.value'));
        $this->assertSame('Wash at 30°C', data_get($hydrated_data, 'composition_and_care_data.translations.1.care.items.0.value'));

        $service->syncRelations($variant, []);

        $this->assertDatabaseCount('product_variant_size_guides', 0);
        $this->assertDatabaseCount('product_variant_compositions', 0);
        $this->assertDatabaseCount('product_variant_cares', 0);
    }
}
