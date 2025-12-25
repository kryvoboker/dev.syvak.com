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
        $app_settings = get_app_settings();

        return [
            'id'           => $this->id,
            'price'        => format_price(
                $this->price,
                config('app.currency.default_currency_code'),
                (float)config('app.currency.default_exchange_rate')
            ),
            'image_urls'   => multiple_convert_img_and_get_url(
                $this->image,
                (int)$app_settings->image_sizes['search_product']['width'],
                (int)$app_settings->image_sizes['search_product']['height'],
            ),
            'link'         => $this->whenLoaded('slugs', function () {
                $slug = $this->slugs->first();

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
            'discount'     => $this->whenLoaded('productDiscount', function () {
                $product_discount = $this->productDiscount->first();

                return $product_discount ? [
                    'id'               => $product_discount->id,
                    'discounted_price' => format_price(
                        $product_discount->price,
                        config('app.currency.default_currency_code'),
                        (float)config('app.currency.default_exchange_rate')
                    ),
                    'start_date'       => $product_discount->date_start,
                    'end_date'         => $product_discount->date_end,
                ] : null;
            })
        ];
    }
}
