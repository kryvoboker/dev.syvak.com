<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\TotalTypesEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use App\Models\Orders\OrderProducts;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderTotals;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class ThankYouOrderDataService
{
    /**
     * @return array<string, mixed>|null
     */
    public function getByOrderNumber(string $order_number, string $locale): ?array
    {
        try {
            $language = resolve_language_by_locale($locale);
            $language_id = $language instanceof Language ? $this->integerValue($language->getKey()) : null;

            $order = Orders::query()
                ->with([
                    'status',
                    'customer',
                    'shipping',
                    'payments' => fn ($query) => $query->latest('id'),
                    'products.productVariant.images',
                    'products.productVariant.attributeValues' => function ($query) use ($language_id): void {
                        if ($language_id !== null) {
                            $query->where('language_id', $language_id);
                        }
                    },
                    'products.productVariant.attributeValues.attribute.attributeDescription' => function ($query) use ($language_id): void {
                        if ($language_id !== null) {
                            $query->where('language_id', $language_id);
                        }
                    },
                    'products.product.productImage',
                    'totals' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                ])
                ->where('order_number', $order_number)
                ->first();

            if (! $order instanceof Orders) {
                return null;
            }

            return $this->mapOrder($order);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[ThankYouOrderDataService.getByOrderNumber] order lookup failed', [
                'order_number' => $order_number,
                'locale' => $locale,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOrder(Orders $order): array
    {
        $currency_code = $this->stringValue($order->currency_code);
        $exchange_rate = $this->floatValue($order->exchange_rate);
        $shipping = $order->shipping;
        $payment = $order->payments->first();

        return [
            'order_number' => $this->stringValue($order->order_number),
            'status' => $this->stringValue($order->order_status_name ?: $order->status?->code ?: '—'),
            'customer' => [
                'name' => Str::squish(sprintf('%s %s', $this->stringValue(data_get($order->customer, 'first_name', '')), $this->stringValue(data_get($order->customer, 'last_name', '')))),
                'email' => $this->stringValue(data_get($order->customer, 'email', '')),
                'phone' => $this->stringValue(data_get($order->customer, 'telephone', '')),
            ],
            'delivery' => [
                'method' => $this->resolveDeliveryMethod($this->nullableString(data_get($shipping, 'code')), $this->nullableString(data_get($shipping, 'method'))),
                'address' => $this->resolveDeliveryAddress(
                    $this->nullableString(data_get($shipping, 'city')),
                    $this->nullableString(data_get($shipping, 'address')),
                    $this->nullableString(data_get($shipping, 'delivery_point')),
                ),
            ],
            'products' => $order->products
                ->map(fn (OrderProducts $product): array => $this->mapProduct($product, $currency_code, $exchange_rate))
                ->values()
                ->all(),
            'summary' => [
                'payment_method' => $this->stringValue(data_get($payment, 'method') ?: data_get($payment, 'code') ?: '—'),
                'delivery_method' => $this->resolveDeliveryMethod($this->nullableString(data_get($shipping, 'code')), $this->nullableString(data_get($shipping, 'method'))),
                'delivery_address' => $this->resolveDeliveryAddress(
                    $this->nullableString(data_get($shipping, 'city')),
                    $this->nullableString(data_get($shipping, 'address')),
                    $this->nullableString(data_get($shipping, 'delivery_point')),
                ),
                'subtotal' => $this->formatTotal($order, TotalTypesEnum::Subtotal, $currency_code, $exchange_rate),
                'packaging' => '—',
                'delivery_cost' => $this->formatTotal($order, TotalTypesEnum::Shipping, $currency_code, $exchange_rate),
                'total' => $this->formatMoney($this->floatValue($order->total), $currency_code, $exchange_rate),
                'notes' => $this->stringValue($order->comment ?? ''),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProduct(OrderProducts $order_product, string $currency_code, float $exchange_rate): array
    {
        $variant = $order_product->productVariant;
        $variant_images = data_get($variant, 'images');
        $product_images = data_get($order_product->product, 'productImage');
        $variant_image = $variant_images instanceof Collection
            ? data_get($variant_images->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])->first(), 'image')
            : null;
        $variant_image = data_get($variant, 'image') ?: $variant_image;
        $product_image = $product_images instanceof Collection
            ? data_get($product_images->sortBy('sort_order')->first(), 'image')
            : null;
        $image_path = $variant_image
            ?? data_get($variant, 'image')
            ?? $product_image
            ?? data_get($order_product->product, 'image');

        return [
            'image_url' => filled($image_path) ? convert_img_and_get_url($this->stringValue($image_path), 220, 220) : null,
            'name' => $this->stringValue($order_product->name),
            'sku' => $this->stringValue($order_product->sku ?: $order_product->model ?: '—'),
            'attributes' => $this->mapAttributes($variant?->attributeValues),
            'quantity' => $this->integerValue($order_product->quantity),
            'price_formatted' => $this->formatMoney($this->floatValue($order_product->line_total), $currency_code, $exchange_rate),
        ];
    }

    /**
     * @param Collection<int, ProductVariantAttributeValue>|null $attribute_values
     * @return array<string, string>
     */
    private function mapAttributes(?Collection $attribute_values): array
    {
        if ($attribute_values === null) {
            return [];
        }

        return $attribute_values
            ->mapWithKeys(function (ProductVariantAttributeValue $attribute_value): array {
                $name = Str::trim($this->stringValue($attribute_value->attribute?->attributeDescription->first()?->name));
                $value = Str::trim($this->stringValue($attribute_value->value_string));

                if ($name === '' || $value === '') {
                    return [];
                }

                return [$name => $value];
            })
            ->all();
    }

    private function resolveDeliveryMethod(?string $code, ?string $stored_method): string
    {
        if ($code === null || $code === '') {
            return $this->stringValue($stored_method ?? '—');
        }

        $translation_key = match ($code) {
            'nova_poshta' => 'storefront/pages/checkout.delivery_methods.nova_poshta',
            'nova_poshta_poshtomat' => 'storefront/pages/checkout.delivery_methods.nova_poshta_poshtomat',
            'nova_poshta_courier' => 'storefront/pages/checkout.delivery_methods.nova_poshta_courier',
            'ukr_poshta' => 'storefront/pages/checkout.delivery_methods.ukr_poshta',
            default => null,
        };

        if ($translation_key === null) {
            return $this->stringValue($stored_method ?: $code);
        }

        $localized_method = Str::trim($this->stringValue(Lang::get($translation_key)));

        return $localized_method !== $translation_key ? $localized_method : $this->stringValue($stored_method ?: $code);
    }

    private function resolveDeliveryAddress(?string $city, ?string $address, ?string $delivery_point): string
    {
        return collect([$city, $address, $delivery_point])
            ->map(fn (?string $value): string => Str::trim($this->stringValue($value)))
            ->filter()
            ->implode(', ');
    }

    private function formatTotal(Orders $order, TotalTypesEnum $type, string $currency_code, float $exchange_rate): string
    {
        $total = $order->totals->first(function ($item) use ($type): bool {
            $item_type = $item->total_type;

            return $item_type === $type;
        });

        return $total instanceof OrderTotals
            ? $this->formatMoney((float) $total->value, $currency_code, $exchange_rate)
            : '—';
    }

    private function formatMoney(float $amount, string $currency_code, float $exchange_rate): string
    {
        return replace_currency_symbol_to_code(format_price($amount, $currency_code, $exchange_rate));
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = $this->stringValue($value);

        return $value !== '' ? $value : null;
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
