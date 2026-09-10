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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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
        $language_id = $language instanceof Language ? integer_value($language->id) : 0;

        if ($cart_items === []) {
            return $this->emptyPayload($mode);
        }

        $variant_ids = collect($cart_items)
            ->pluck('product_variant_id')
            ->map(fn (mixed $variant_id): int => integer_value($variant_id))
            ->filter(fn (int $variant_id): bool => $variant_id > 0)
            ->unique()
            ->values()
            ->all();

        if ($variant_ids === []) {
            return $this->emptyPayload($mode);
        }

        /** @var Collection<int, ProductVariant> $variants */
        $variants = ProductVariant::query()
            ->select([
                'id',
                'product_id',
                'price',
                'image',
                'quantity',
                'minimum',
            ])
            ->with([
                'descriptions' => function (Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'slugs' => function (Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'images' => function (Relation $query): void {
                    $query->orderBy('sort_order')->orderBy('id');
                },
                'product' => function (Relation $query): void {
                    $query->select([
                        'id',
                        'price',
                        'minimum',
                        'model',
                        'sku',
                        'ean',
                    ])->with('categories:id');
                },
                'product.productDescription' => function (Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'product.slugs' => function (Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'attributeValues' => function (Relation $query) use ($language_id): void {
                    $query
                        ->with([
                            'attribute' => function (Relation $query) use ($language_id): void {
                                $query
                                    ->where('is_active', true)
                                    ->with([
                                        'attributeDescription' => function (Relation $query) use ($language_id): void {
                                            $query->where('language_id', $language_id);
                                        },
                                    ]);
                            },
                        ])
                        ->where('language_id', $language_id);
                },
                'discounts' => function (Relation $query): void {
                    $query
                        ->where('user_group_id', get_app_settings()->user_group_id ?? 0)
                        ->where('date_start', '<=', now())
                        ->where('date_end', '>=', now())
                        ->orderBy('priority')
                        ->orderByDesc('updated_at');
                },
            ])
            ->whereIn('id', $variant_ids)
            ->get()
            ->keyBy('id');

        $resolved_items = collect($cart_items)
            ->map(function (array $item_data) use ($variants, $language_id): ?array {
                $variant = $variants->get(integer_value($item_data['product_variant_id']));

                if (!$variant instanceof ProductVariant || !$variant->product) {
                    return null;
                }

                $quantity = max(1, integer_value(Arr::get($item_data, 'quantity', 1)));
                $rrc_price = max(0, float_value($variant->price ?? $variant->product->price ?? 0));
                $active_discount = $variant->discounts->first();
                $price = max(0, float_value($active_discount->price ?? $rrc_price));
                $is_discounted = $active_discount !== null && $price < $rrc_price;

                $variant_name = Str::trim(string_value($variant->descriptions->first()?->name));
                $product_name = Str::trim(string_value($variant->product->productDescription->first()?->name));
                $item_name = filled($variant_name) ? $variant_name : $product_name;

                $product_slug = Str::trim(string_value($variant->product->slugs->first()?->slug));
                $variant_slug = Str::trim(string_value($variant->slugs->first()?->slug));

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

                $line_total = (float) $price * (float) $quantity;

                $selected_attributes = $this->resolveItemAttributes(
                    $variant,
                    array_value(Arr::get($item_data, 'chosen_attributes', [])),
                );

                return [
                    'cart_id' => integer_value(Arr::get($item_data, 'cart_id', 0)),
                    'variant_id' => integer_value($variant->id),
                    'product_id' => integer_value($variant->product_id),
                    'language_id' => $language_id,
                    'model' => $variant->product->model,
                    'sku' => string_value($variant->product->sku),
                    'ean' => $variant->product->ean,
                    'name' => $item_name,
                    'url' => $item_url,
                    'quantity' => $quantity,
                    'minimum_quantity' => max(1, integer_value($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'available_quantity' => max(0, integer_value($variant->quantity)),
                    'is_in_stock' => integer_value($variant->quantity) >= max(1, integer_value($variant->minimum ?: $variant->product->minimum ?: 1)),
                    'unit_price' => $price,
                    'unit_price_formatted' => replace_currency_symbol_to_code((string) format_price(
                        $price,
                        nullable_string(config('app.currency.current_currency_code')),
                        float_value(config('app.currency.current_exchange_rate')),
                    )),
                    'line_total' => $line_total,
                    'rrc_unit_price' => $rrc_price,
                    'rrc_line_total' => (float) $rrc_price * (float) $quantity,
                    'is_discounted' => $is_discounted,
                    'active_discount_id' => $active_discount?->getKey(),
                    'category_ids' => $variant->product->categories->modelKeys(),
                    'line_total_formatted' => replace_currency_symbol_to_code((string) format_price(
                        $line_total,
                        nullable_string(config('app.currency.current_currency_code')),
                        float_value(config('app.currency.current_exchange_rate')),
                    )),
                    'attributes' => $selected_attributes,
                    'selected_attributes' => $selected_attributes,
                    'chosen_attributes_raw' => array_value(Arr::get($item_data, 'chosen_attributes', [])),
                    'image_data' => $image_data,
                ];
            })
            ->filter(fn (?array $item_data): bool => is_array($item_data))
            ->values();

        /** @var Collection<int, array<string, mixed>> $resolved_items */
        return $this->collectCartData($resolved_items, $mode, $locale);
    }

    /**
     * @param array<int|string, mixed> $chosen_attributes
     *
     * @return array<int, array{label: string, value: string}>
     * @psalm-suppress InvalidTemplateParam
     */
    private function resolveItemAttributes(ProductVariant $variant, array $chosen_attributes): array
    {
        $attribute_label_map = $this->resolveVariantAttributeLabelMap($variant);

        $resolved_attributes = collect($chosen_attributes)
            ->map(function (mixed $value, int|string $key): ?array {
                if (is_array($value)) {
                    $raw_attribute_id = Arr::get($value, 'attribute_id');
                    $attribute_id = is_numeric($raw_attribute_id) ? integer_value($raw_attribute_id) : null;
                    $label = Str::trim(string_value(Arr::get(
                        $value,
                        'label',
                        Arr::get($value, 'attribute_name', Arr::get($value, 'name', $key)),
                    )));
                    $text = Str::trim(string_value(Arr::get(
                        $value,
                        'value',
                        Arr::get($value, 'text', Arr::get($value, 'attribute_value', '')),
                    )));
                } else {
                    $attribute_id = is_int($key) || ctype_digit($key) ? integer_value($key) : null;
                    $label = Str::trim(string_value($key));
                    $text = Str::trim(string_value($value));
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
            ->filter(fn (array|null $attribute): bool => $attribute !== null)
            ->map(function (array|null $attribute) use ($attribute_label_map): array {
                if ($attribute === null) {
                    return [
                        'label' => '',
                        'value' => '',
                    ];
                }

                $attribute_id = Arr::get($attribute, 'attribute_id');
                $label = Str::trim(string_value(Arr::get($attribute, 'label', '')));

                if (is_int($attribute_id) && filled($attribute_label_map[$attribute_id] ?? null)) {
                    $label = $attribute_label_map[$attribute_id];
                }

                return [
                    'label' => $label,
                    'value' => Str::trim(string_value(Arr::get($attribute, 'value', ''))),
                ];
            })
            ->filter(fn (array $attribute): bool => $attribute['value'] !== '')
            ->values();

        if ($resolved_attributes->isNotEmpty()) {
            return $resolved_attributes->all();
        }

        /** @var array<int, array{label: string, value: string}> $attributes */
        $attributes = $variant->attributeValues
            ->map(function ($attribute_value): ?array {
                $value_string = Str::trim(string_value($attribute_value->value_string));

                if ($value_string === '') {
                    return null;
                }

                $attribute_label = Str::trim(string_value($attribute_value->attribute?->attributeDescription->first()?->name));

                return [
                    'label' => $attribute_label,
                    'value' => $value_string,
                ];
            })
            ->filter(fn (?array $attribute): bool => is_array($attribute))
            ->values()
            ->all();

        return $attributes;
    }

    /**
     * @return array<int, string>
     */
    private function resolveVariantAttributeLabelMap(ProductVariant $variant): array
    {
        $label_map = [];

        foreach ($variant->attributeValues as $attribute_value) {
            $attribute_id = integer_value($attribute_value->attribute_id ?? 0);

            if ($attribute_id <= 0) {
                continue;
            }

            $attribute_label = Str::trim(string_value($attribute_value->attribute?->attributeDescription->first()?->name));

            if ($attribute_label === '') {
                continue;
            }

            $label_map[$attribute_id] = $attribute_label;
        }

        return $label_map;
    }

    private function resolveVariantImagePath(ProductVariant $variant): ?string
    {
        $variant_image = Str::trim(string_value($variant->image));

        if (filled($variant_image)) {
            return $variant_image;
        }

        $image_from_images = Str::trim(string_value(data_get($variant->images->first(), 'image')));

        if (filled($image_from_images)) {
            return $image_from_images;
        }

        $product_image = Str::trim(string_value(data_get($variant->product, 'image')));

        return filled($product_image) ? $product_image : null;
    }

    /**
     * @param Collection<int, array<string, mixed>> $resolved_items
     * @return array<int, callable(array<string, mixed>): array<string, mixed>>
     */
    private function resolveTotalsCallbacks(Collection $resolved_items, string $locale): array
    {
        try {
            $checkout_state = (array)session()->get('checkout.selection_state', []);
            $app_settings = get_app_settings();

            return [
                app(UkrPoshtaDeliveryModule::class)->resolveCallback(),
                app(NovaPoshtaDeliveryModule::class)->resolveCallback(),
                app(PromoCodeModule::class)->resolveCallback([
                    'cart_items' => $resolved_items->all(),
                    'code' => $checkout_state['promo_code'] ?? null,
                    'locale' => $locale,
                    'user_id' => Auth::id(),
                    'user_group_id' => $app_settings?->user_group_id,
                ]),
                app(GiftCertificateModule::class)->resolveCallback(),
            ];
        } catch (Throwable $e) {
            Log::channel('stack')->error($e->getMessage(), $e->getTrace());

            return [];
        }
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
     * @param Collection<int, array<string, mixed>> $resolved_items
     * @param string                                $mode
     * @param string                                $locale
     *
     * @return array<string, mixed>
     */
    protected function collectCartData(Collection $resolved_items, string $mode, string $locale): array
    {
        $totals = $this->cart_totals_pipeline_service->calculate(
            cart_items: $resolved_items->all(),
            callbacks : $this->resolveTotalsCallbacks($resolved_items, $locale),
        );

        $total_quantity = integer_value($resolved_items->sum(fn (array $item_data): int => integer_value($item_data['quantity'])));

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
