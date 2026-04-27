<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\ProductVariant;
use App\Services\Cart\Modules\Delivery\NovaPoshtaDeliveryModule;
use App\Services\Cart\Modules\Delivery\UkrPoshtaDeliveryModule;
use App\Services\Cart\Modules\Discount\GiftCertificateModule;
use App\Services\Cart\Modules\Discount\PromoCodeModule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class CartViewDataBuilderService
{
    public function __construct(
        private CartTotalsPipelineService $cart_totals_pipeline_service,
    ) {}

    /**
     * @param  array<int, array{product_variant_id: int, quantity: int, added_at: string}>  $session_items
     * @return array<string, mixed>
     */
    public function build(array $session_items, string $locale, string $mode = 'regular'): array
    {
        $language    = resolve_language_by_locale($locale);
        $language_id = $language instanceof Language ? (int) $language->id : 0;

        if ($session_items === []) {
            return $this->emptyPayload($mode);
        }

        $variant_ids = collect($session_items)
            ->pluck('product_variant_id')
            ->map(fn (mixed $variant_id): int => (int) $variant_id)
            ->filter(fn (int $variant_id): bool => $variant_id > 0)
            ->unique()
            ->values()
            ->all();

        if ($variant_ids === []) {
            return $this->emptyPayload($mode);
        }

        /** @var Collection<int, ProductVariant> $variants */
        $variants = ProductVariant::query()
            ->with([
                'descriptions'               => fn ($query) => $query->where('language_id', $language_id),
                'slugs'                      => fn ($query) => $query->where('language_id', $language_id),
                'images'                     => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'product.productDescription' => fn ($query) => $query->where('language_id', $language_id),
                'product.slugs'              => fn ($query) => $query->where('language_id', $language_id),
            ])
            ->whereIn('id', $variant_ids)
            ->get()
            ->keyBy('id');

        $items = collect($session_items)
            ->map(function (array $item_data) use ($variants, $language_id): ?array {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get((int) $item_data['product_variant_id']);

                if (! $variant instanceof ProductVariant || ! $variant->product) {
                    return null;
                }

                $quantity = max(1, (int) Arr::get($item_data, 'quantity', 1));
                $price    = max(0, (float) ($variant->price ?? $variant->product->price ?? 0));

                $variant_name = Str::trim((string) optional($variant->descriptions->first())->name);
                $product_name = Str::trim((string) optional($variant->product->productDescription->first())->name);
                $item_name    = filled($variant_name) ? $variant_name : $product_name;

                $product_slug = Str::trim((string) optional($variant->product->slugs->first())->slug);
                $variant_slug = Str::trim((string) optional($variant->slugs->first())->slug);

                $item_url = filled($product_slug)
                    ? (filled($variant_slug)
                        ? localized_route('localized.catalog.product.variant.show', [
                            'slug'         => $product_slug,
                            'variant_slug' => $variant_slug,
                        ])
                        : localized_route('localized.catalog.product.show', ['slug' => $product_slug]))
                    : '#';

                $image_path = $this->resolveVariantImagePath($variant);
                $image_data = [
                    'path'   => $image_path,
                    'urls'   => multiple_convert_img_and_get_url($image_path, 220, 220),
                    'width'  => 220,
                    'height' => 220,
                ];

                $line_total = $price * $quantity;

                return [
                    'variant_id'           => (int) $variant->id,
                    'product_id'           => (int) $variant->product_id,
                    'language_id'          => $language_id,
                    'sku'                  => (string) $variant->product->sku,
                    'name'                 => $item_name,
                    'url'                  => $item_url,
                    'quantity'             => $quantity,
                    'minimum_quantity'     => max(1, (int) ($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'available_quantity'   => max(0, (int) $variant->quantity),
                    'is_in_stock'          => (int) $variant->quantity >= max(1, (int) ($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'unit_price'           => $price,
                    'unit_price_formatted' => replace_currency_symbol_to_code(
                        format_price(
                            $price,
                            config('app.currency.current_currency_code'),
                            (float) config('app.currency.current_exchange_rate'),
                        ),
                    ),
                    'line_total'           => $line_total,
                    'line_total_formatted' => replace_currency_symbol_to_code(
                        format_price(
                            $line_total,
                            config('app.currency.current_currency_code'),
                            (float) config('app.currency.current_exchange_rate'),
                        ),
                    ),
                    'image_data' => $image_data,
                ];
            })
            ->filter(fn (?array $item_data): bool => is_array($item_data))
            ->values();

        $totals = $this->cart_totals_pipeline_service->calculate(
            cart_items: $items->all(),
            callbacks : $this->resolveTotalsCallbacks(),
        );

        $total_quantity = (int) $items->sum(fn (array $item_data): int => (int) $item_data['quantity']);

        return [
            'mode'           => $mode,
            'items'          => $items->all(),
            'first_item'     => $items->first(),
            'hidden_items'   => $items->slice(1)->values()->all(),
            'items_count'    => $items->count(),
            'total_quantity' => $total_quantity,
            'is_empty'       => $items->isEmpty(),
            'totals'         => $totals,
            'routes'         => [
                'modal_index'    => localized_route('localized.catalog.cart-modal-ajax.index'),
                'modal_store'    => localized_route('localized.catalog.cart-modal-ajax.store'),
                'cart_store'     => localized_route('localized.catalog.cart.store'),
                'order_validate' => localized_route('localized.catalog.order-confirm.validate'),
                'order_store'    => localized_route('localized.catalog.order-confirm.store'),
            ],
        ];
    }

    private function resolveVariantImagePath(ProductVariant $variant): ?string
    {
        $image_from_images = Str::trim((string) optional($variant->images->first())->image);

        if (filled($image_from_images)) {
            return $image_from_images;
        }

        $variant_image = Str::trim((string) $variant->image);

        if (filled($variant_image)) {
            return $variant_image;
        }

        $product_image = Str::trim((string) optional($variant->product)->image);

        return filled($product_image) ? $product_image : null;
    }

    /**
     * @return array<int, callable(array<string, mixed>): array<string, mixed>>
     */
    private function resolveTotalsCallbacks(): array
    {
        return [
            app(UkrPoshtaDeliveryModule::class)->resolveCallback(),
            app(NovaPoshtaDeliveryModule::class)->resolveCallback(),
            app(PromoCodeModule::class)->resolveCallback(),
            app(GiftCertificateModule::class)->resolveCallback(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(string $mode): array
    {
        $totals = $this->cart_totals_pipeline_service->calculate([]);

        return [
            'mode'           => $mode,
            'items'          => [],
            'first_item'     => null,
            'hidden_items'   => [],
            'items_count'    => 0,
            'total_quantity' => 0,
            'is_empty'       => true,
            'totals'         => $totals,
            'routes'         => [
                'modal_index'    => localized_route('localized.catalog.cart-modal-ajax.index'),
                'modal_store'    => localized_route('localized.catalog.cart-modal-ajax.store'),
                'cart_store'     => localized_route('localized.catalog.cart.store'),
                'order_validate' => localized_route('localized.catalog.order-confirm.validate'),
                'order_store'    => localized_route('localized.catalog.order-confirm.store'),
            ],
        ];
    }
}
