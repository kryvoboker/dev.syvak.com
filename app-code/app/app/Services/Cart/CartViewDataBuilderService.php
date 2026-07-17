<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Enums\Cart\CartModeEnum;
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
    ) {
    }

    /**
     * @param array<int, array{cart_id: int, product_variant_id: int, quantity: int, chosen_attributes: array<int|string, mixed>, added_at: string}> $cart_items
     *
     * @return array<string, mixed>
     */
    public function build(array $cart_items, string $locale, string $mode = CartModeEnum::Regular->value): array
    {
        $language = resolve_language_by_locale($locale);
        $language_id = $language instanceof Language ? (int)$language->id : 0;

        if ($cart_items === []) {
            return $this->emptyPayload($mode);
        }

        $variant_ids = collect($cart_items)
            ->pluck('product_variant_id')
            ->map(fn (mixed $variant_id): int => (int)$variant_id)
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
                'descriptions' => fn ($query) => $query->where('language_id', $language_id),
                'slugs' => fn ($query) => $query->where('language_id', $language_id),
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'product.productDescription' => fn ($query) => $query->where('language_id', $language_id),
                'product.slugs' => fn ($query) => $query->where('language_id', $language_id),
                'attributeValues' => function ($query) use ($language_id) {
                    return $query
                        ->with([
                            'attribute' => function ($query) use ($language_id) {
                                return $query
                                    ->where('is_active', true)
                                    ->with([
                                        'attributeDescription' => fn ($query) => $query->where('language_id', $language_id),
                                    ]);
                            },
                        ])
                        ->where('language_id', $language_id);
                },
            ])
            ->whereIn('id', $variant_ids)
            ->get()
            ->keyBy('id');

        $resolved_items = collect($cart_items)
            ->map(function (array $item_data) use ($variants, $language_id): ?array {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get((int)$item_data['product_variant_id']);

                if (!$variant instanceof ProductVariant || !$variant->product) {
                    return null;
                }

                $quantity = max(1, (int)Arr::get($item_data, 'quantity', 1));
                $price = max(0, (float)($variant->price ?? $variant->product->price ?? 0));

                $variant_name = Str::trim((string)$variant->descriptions->first()?->name);
                $product_name = Str::trim((string)$variant->product->productDescription->first()?->name);
                $item_name = filled($variant_name) ? $variant_name : $product_name;

                $product_slug = Str::trim((string)$variant->product->slugs->first()?->slug);
                $variant_slug = Str::trim((string)$variant->slugs->first()?->slug);

                if (filled($product_slug)) {
                    if (filled($variant_slug)) {
                        $item_url = localized_route('localized.catalog.product.variant.show', [
                            'slug' => $product_slug,
                            'variant_slug' => $variant_slug,
                        ]);
                    } else {
                        $item_url = localized_route('localized.catalog.product.show', ['slug' => $product_slug]);
                    }
                } else {
                    $item_url = '#';
                }

                $image_path = $this->resolveVariantImagePath($variant);
                $image_data = [
                    'path' => $image_path,
                    'urls' => multiple_convert_img_and_get_url($image_path, 220, 220),
                    'width' => 220,
                    'height' => 220,
                ];

                $line_total = $price * $quantity;

                $selected_attributes = $this->resolveItemAttributes(
                    $variant,
                    (array)Arr::get($item_data, 'chosen_attributes', []),
                );

                return [
                    'cart_id' => (int)Arr::get($item_data, 'cart_id', 0),
                    'variant_id' => (int)$variant->id,
                    'product_id' => (int)$variant->product_id,
                    'language_id' => $language_id,
                    'sku' => (string)$variant->product->sku,
                    'name' => $item_name,
                    'url' => $item_url,
                    'quantity' => $quantity,
                    'minimum_quantity' => max(1, (int)($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'available_quantity' => max(0, (int)$variant->quantity),
                    'is_in_stock' => (int)$variant->quantity >= max(1, (int)($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'unit_price' => $price,
                    'unit_price_formatted' => replace_currency_symbol_to_code(format_price(
                        $price,
                        config('app.currency.current_currency_code'),
                        (float)config('app.currency.current_exchange_rate'),
                    )),
                    'line_total' => $line_total,
                    'line_total_formatted' => replace_currency_symbol_to_code(format_price(
                        $line_total,
                        config('app.currency.current_currency_code'),
                        (float)config('app.currency.current_exchange_rate'),
                    )),
                    'attributes' => $selected_attributes,
                    'selected_attributes' => $selected_attributes,
                    'chosen_attributes_raw' => (array)Arr::get($item_data, 'chosen_attributes', []),
                    'image_data' => $image_data,
                ];
            })
            ->filter(fn (?array $item_data): bool => is_array($item_data))
            ->values();

        return $this->collectCartData($resolved_items, $mode);
    }

    /**
     * @param array<int|string, mixed> $chosen_attributes
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function resolveItemAttributes(ProductVariant $variant, array $chosen_attributes): array
    {
        $attribute_label_map = $this->resolveVariantAttributeLabelMap($variant);

        $resolved_attributes = collect($chosen_attributes)
            ->map(function (mixed $value, int|string $key): ?array {
                if (is_array($value)) {
                    $raw_attribute_id = Arr::get($value, 'attribute_id');
                    $attribute_id = is_numeric($raw_attribute_id) ? (int)$raw_attribute_id : null;
                    $label = Str::trim((string)Arr::get(
                        $value,
                        'label',
                        Arr::get($value, 'attribute_name', Arr::get($value, 'name', $key)),
                    ));
                    $text = Str::trim((string)Arr::get(
                        $value,
                        'value',
                        Arr::get($value, 'text', Arr::get($value, 'attribute_value', '')),
                    ));
                } else {
                    $attribute_id = is_int($key) || ctype_digit($key) ? (int)$key : null;
                    $label = Str::trim((string)$key);
                    $text = Str::trim((string)$value);
                }

                if ($text === '') {
                    return null;
                }

                return [
                    'attribute_id' => $attribute_id,
                    'label' => $label,
                    'value' => $text,
                ];
            })
            ->filter(fn (?array $attribute): bool => is_array($attribute))
            ->map(static function (array $attribute) use ($attribute_label_map): array {
                $attribute_id = Arr::get($attribute, 'attribute_id');
                $label = Str::trim((string)Arr::get($attribute, 'label', ''));

                if (is_int($attribute_id) && filled($attribute_label_map[$attribute_id] ?? null)) {
                    $label = $attribute_label_map[$attribute_id];
                }

                return [
                    'label' => $label,
                    'value' => Str::trim((string)Arr::get($attribute, 'value', '')),
                ];
            })
            ->filter(static fn (array $attribute): bool => $attribute['value'] !== '')
            ->values();

        if ($resolved_attributes->isNotEmpty()) {
            return $resolved_attributes->all();
        }

        return $variant->attributeValues
            ->map(function ($attribute_value): ?array {
                $value_string = Str::trim((string)$attribute_value->value_string);

                if ($value_string === '') {
                    return null;
                }

                $attribute_label = Str::trim((string)$attribute_value->attribute?->attributeDescription->first()?->name);

                return [
                    'label' => $attribute_label,
                    'value' => $value_string,
                ];
            })
            ->filter(fn (?array $attribute): bool => is_array($attribute))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveVariantAttributeLabelMap(ProductVariant $variant): array
    {
        $label_map = [];

        foreach ($variant->attributeValues as $attribute_value) {
            $attribute_id = (int)($attribute_value->attribute_id ?? 0);

            if ($attribute_id <= 0) {
                continue;
            }

            $attribute_label = Str::trim((string)$attribute_value->attribute?->attributeDescription->first()?->name);

            if ($attribute_label === '') {
                continue;
            }

            $label_map[$attribute_id] = $attribute_label;
        }

        return $label_map;
    }

    private function resolveVariantImagePath(ProductVariant $variant): ?string
    {
        $image_from_images = Str::trim((string)optional($variant->images->first())->image);

        if (filled($image_from_images)) {
            return $image_from_images;
        }

        $variant_image = Str::trim((string)$variant->image);

        if (filled($variant_image)) {
            return $variant_image;
        }

        $product_image = Str::trim((string)optional($variant->product)->image);

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
            'mode' => $mode,
            'items' => [],
            'first_item' => null,
            'hidden_items' => [],
            'items_count' => 0,
            'total_quantity' => 0,
            'is_empty' => true,
            'totals' => $totals,
            'routes' => [
                'modal_index' => localized_route('localized.catalog.cart-modal-ajax.index'),
                'modal_store' => localized_route('localized.catalog.cart-modal-ajax.store'),
                'cart_store' => localized_route('localized.catalog.cart.store'),
                'order_validate' => localized_route('localized.catalog.order-confirm.validate'),
                'order_store' => localized_route('localized.catalog.order-confirm.store'),
            ],
        ];
    }

    /**
     * @param Collection $resolved_items
     * @param string     $mode
     *
     * @return array
     */
    protected function collectCartData(Collection $resolved_items, string $mode): array
    {
        $totals = $this->cart_totals_pipeline_service->calculate(
            cart_items: $resolved_items->all(),
            callbacks : $this->resolveTotalsCallbacks(),
        );

        $total_quantity = (int)$resolved_items->sum(fn (array $item_data): int => (int)$item_data['quantity']);

        return [
            'mode' => $mode,
            'items' => $resolved_items->all(),
            'first_item' => $resolved_items->first(),
            'hidden_items' => $resolved_items->slice(1)->values()->all(),
            'items_count' => $resolved_items->count(),
            'total_quantity' => $total_quantity,
            'is_empty' => $resolved_items->isEmpty(),
            'totals' => $totals,
            'routes' => [
                'modal_index' => localized_route('localized.catalog.cart-modal-ajax.index'),
                'modal_store' => localized_route('localized.catalog.cart-modal-ajax.store'),
                'cart_store' => localized_route('localized.catalog.cart.store'),
                'order_validate' => localized_route('localized.catalog.order-confirm.validate'),
                'order_store' => localized_route('localized.catalog.order-confirm.store'),
            ],
        ];
    }
}
