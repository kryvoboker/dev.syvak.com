<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Catalogs\Products\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $app_settings         = get_app_settings();
        $search_product_sizes = $app_settings->image_sizes?->firstWhere('name', 'search_product') ?? [];
        $price                = format_price(
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
                    (int) $search_product_sizes['width'],
                    (int) $search_product_sizes['height'],
                ),
                'width'  => (int) $search_product_sizes['width'],
                'height' => (int) $search_product_sizes['height'],
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
