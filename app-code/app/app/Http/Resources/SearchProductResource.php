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
                'width' => $this->integerValue(config('app.page_settings.search.images.search_product.width', 219)),
                'height' => $this->integerValue(config('app.page_settings.search.images.search_product.height', 219)),
            ];

            try {
                $search_product_sizes_cache = app(PageSettingsBootstrapService::class)->getSearchProductImageSize();
            } catch (Throwable) {
                // Keep config fallback when page settings are not available.
            }
        }

        $variant = $this->defaultVariant;
        $price_source = $variant->price ?? $this->price;
        $image_source = $variant->image ?? $this->image;
        $variant_discount = $variant?->discounts?->first();

        $price = format_price(
            $price_source,
            $this->nullableString(config('app.currency.current_currency_code')),
            $this->floatValue(config('app.currency.current_exchange_rate')),
        );

        return [
            'id' => $this->id,
            'sku' => escape_special_html($this->sku),
            'price' => replace_currency_symbol_to_code($price),
            'image_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    $image_source,
                    $this->integerValue($search_product_sizes_cache['width']),
                    $this->integerValue($search_product_sizes_cache['height']),
                ),
                'width' => $this->integerValue($search_product_sizes_cache['width']),
                'height' => $this->integerValue($search_product_sizes_cache['height']),
            ],
            'link' => $this->whenLoaded('slugs', function () {
                $slug = $this->slugs->first()?->slug;

                return $slug ? localized_route('localized.catalog.product.show', compact('slug')) : '';
            }),
            'descriptions' => $this->whenLoaded('productDescription', function () {
                $product_description = $this->productDescription->first();

                return [
                    'id' => $product_description?->id,
                    'name' => escape_special_html($product_description?->name),
                    'description' => escape_special_html($product_description?->description),
                ];
            }),
            'discount' => $this->whenLoaded('defaultVariant', function () use ($variant_discount) {
                if ($variant_discount !== null) {
                    $discounted_price = format_price(
                        $variant_discount->price,
                        $this->nullableString(config('app.currency.current_currency_code')),
                        $this->floatValue(config('app.currency.current_exchange_rate')),
                    );

                    return [
                        'id' => $variant_discount->id,
                        'discounted_price' => replace_currency_symbol_to_code($discounted_price),
                        'start_date' => $variant_discount->date_start,
                        'end_date' => $variant_discount->date_end,
                    ];
                }

                return [];
            }),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && filled($value) ? (string) $value : null;
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
