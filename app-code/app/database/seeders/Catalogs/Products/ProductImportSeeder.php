<?php

declare(strict_types=1);

namespace Database\Seeders\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use App\Models\ApplicationSettings\Language;
use App\Supports\Services\Ai\AiTranslationService;
use App\Supports\Services\SeoSlug\EnSeoSlugService;
use App\Supports\Services\SeoSlug\UaSeoSlugService;
use Illuminate\Database\Seeder;
use Throwable;

class ProductImportSeeder extends Seeder
{
    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $path = database_path('seeders/Catalogs/Products/data/products_2025_12.json');

        if (! is_file($path)) {
            $this->command?->error("Import file not found: $path!");

            return;
        }

        $default_locale = config('app.default_locale');
        $language       = new Language();

        // Get current locale language ID (adjust based on your logic)
        $current_language_id = $language->getLanguageByCode($default_locale)?->id;

        if ($current_language_id === null) {
            $this->command?->error("$default_locale language not found in the database!");

            return;
        }

        $languages              = $language->getActiveLanguagesWithoutExceptCode($default_locale);
        $ai_translation_service = app(AiTranslationService::class);

        /** @var array<int, array{sku:string, ean:string|null, price:float|null, name:string, image:string|null}> $items */
        $items = json_decode(file_get_contents($path), true) ?? [];

        foreach ($items as $item) {
            $sku = trim((string) ($item['sku'] ?? ''));

            if ($sku === '') {
                continue;
            }

            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'model'          => $sku,
                    'ean'            => $item['ean'] ?? null,
                    'price'          => (float) ($item['price'] ?? 0),
                    'image'          => $item['image'] ?? null, // "images/products/2025/12/<sku>.<ext>"
                    'quantity'       => 0,
                    'minimum'        => 1,
                    'viewed'         => 0,
                    'is_active'      => true,
                    'date_available' => now(config('app.timezone')),
                    'date_added'     => now(config('app.timezone')),
                ],
            );

            $product_name = html_entity_decode((string) ($item['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

            $product->productDescription()->updateOrCreate(
                [
                    'product_id'  => $product->id,
                    'language_id' => $current_language_id,
                ],
                [
                    'name'        => $product_name,
                    'description' => null,
                ],
            );

            $product->slugs()->updateOrCreate(
                ['language_id' => $current_language_id],
                ['slug' => UaSeoSlugService::make($item['name'] ?? '', 500)],
            );

            $languages->each(function (Language $lang) use ($ai_translation_service, $product, $product_name, $default_locale) {
                $prompt = "Translate the product name from $default_locale to $lang->code. The product name is: $product_name.";

                $translated_name = $ai_translation_service->productName($product->id, $prompt);

                $product->productDescription()->updateOrCreate(
                    [
                        'product_id'  => $product->id,
                        'language_id' => $lang->id,
                    ],
                    [
                        'name'        => $translated_name,
                        'description' => null,
                    ],
                );

                $product->slugs()->updateOrCreate(
                    ['language_id' => $lang->id],
                    ['slug' => EnSeoSlugService::make($translated_name, 500)],
                );
            });
        }
    }
}
