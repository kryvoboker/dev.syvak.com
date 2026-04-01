<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Catalogs\Products\Product;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

/**
 * @mixin Product
 */
class SearchProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        static $search_product_sizes_cache = null;

        if (! is_array($search_product_sizes_cache)) {
            $search_product_sizes_cache = [
                'width'  => (int) config('app.page_settings.search.images.search_product.width', 219),
                'height' => (int) config('app.page_settings.search.images.search_product.height', 219),
            ];

            try {
                $search_product_sizes_cache = app(PageSettingsBootstrapService::class)->getSearchProductImageSize();
            } catch (Throwable) {
                // Keep config fallback when page settings are not available.
            }
        }

        $price = format_price(
            $this->price,
            config('app.currency.current_currency_code'),
            (float) config('app.currency.current_exchange_rate'),
        );

        return [
            'id'         => $this->id,
            'sku'        => escape_special_html($this->sku),
            'price'      => replace_currency_symbol_to_code($price),
            'image_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    $this->image,
                    (int) $search_product_sizes_cache['width'],
                    (int) $search_product_sizes_cache['height'],
                ),
                'width'  => (int) $search_product_sizes_cache['width'],
                'height' => (int) $search_product_sizes_cache['height'],
            ],
            'link' => $this->whenLoaded('slugs', function () {
                $slug = $this->slugs->first()?->slug;

                return $slug ? localizedRoute('localized.catalog.product.show', compact('slug')) : '';
            }),
            'descriptions' => $this->whenLoaded('productDescription', function () {
                $product_description = $this->productDescription->first();

                return [
                    'id'          => $product_description?->id,
                    'name'        => escape_special_html($product_description?->name),
                    'description' => escape_special_html($product_description?->description),
                ];
            }),
            'discount' => $this->whenLoaded('productDiscount', function () {
                $product_discount = $this->productDiscount->first();

                if ($product_discount !== null) {
                    $discounted_price = format_price(
                        $product_discount->price,
                        config('app.currency.current_currency_code'),
                        (float) config('app.currency.current_exchange_rate'),
                    );

                    return [
                        'id'               => $product_discount->id,
                        'discounted_price' => replace_currency_symbol_to_code($discounted_price),
                        'start_date'       => $product_discount->date_start,
                        'end_date'         => $product_discount->date_end,
                    ];
                }

                return [];
            }),
        ];
    }
}
