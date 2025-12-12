<?php

declare(strict_types=1);

namespace Database\Seeders\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use App\Models\Settings\Language;
use App\Supports\Services\SeoSlug\UaSeoSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductImportSeeder extends Seeder
{
    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $path = database_path('seeders/Catalogs/Products/data/products_2025_12.json');

        if (!is_file($path)) {
            $this->command?->error("Import file not found: $path!");

            return;
        }

        $language = new Language();

        // Get current locale language ID (adjust based on your logic)
        $current_language_id = $language->getLanguageByCode('uk')?->id;

        if ($current_language_id === null) {
            $this->command?->error('UK language not found in the database!');

            return;
        }

        /** @var array<int, array{sku:string, ean:string|null, price:float|null, name:string, image:string|null}> $items */
        $items = json_decode(file_get_contents($path), true) ?? [];

        DB::transaction(function () use ($items, $current_language_id) {
            foreach ($items as $item) {
                $sku = trim((string)($item['sku'] ?? ''));

                if ($sku === '') {
                    continue;
                }

                $product = Product::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'model'          => $sku,
                        'ean'            => $item['ean'] ?? null,
                        'price'          => (float)($item['price'] ?? 0),
                        'image'          => $item['image'] ?? null, // "images/products/2025/12/<sku>.<ext>"
                        'quantity'       => 0,
                        'minimum'        => 1,
                        'viewed'         => 0,
                        'is_active'      => true,
                        'date_available' => now(config('app.timezone')),
                        'date_added'     => now(config('app.timezone')),
                    ]
                );

                $product->productDescription()->updateOrCreate(
                    [
                        'product_id'  => $product->id,
                        'language_id' => $current_language_id,
                    ],
                    [
                        'name'        => html_entity_decode((string)($item['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'),
                        'description' => null,
                    ]
                );

                $product->slugs()->updateOrCreate(
                    ['language_id' => $current_language_id],
                    ['slug' => UaSeoSlug::make($item['name'] ?? '', 500)]
                );
            }
        });
    }
}
