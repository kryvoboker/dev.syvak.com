<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\OrderNotificationOutcomeEnum;
use App\Enums\Order\TotalTypesEnum;
use App\Models\Orders\Orders;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class OrderNotificationPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Orders $order, OrderNotificationOutcomeEnum $outcome): array
    {
        $order->loadMissing([
            'status',
            'customer',
            'shipping',
            'payments.paymentStatus',
            'products',
            'totals',
            'promoCodeUsages.promoCode',
        ]);

        $payment = $order->payments->sortByDesc('id')->first();
        $promo_usage = $order->promoCodeUsages->sortByDesc('id')->first();
        $promo_total = $order->totals->first(fn ($total): bool => $total->total_type === TotalTypesEnum::PromoCode);

        return [
            'schema_version' => integer_value(config('order-notifications.schema_version', 1)),
            'event_outcome' => $outcome->value,
            'order' => [
                'id' => integer_value($order->getKey()),
                'number' => $order->order_number,
                'status' => ($order->order_status_name ?: $order->status?->code ?: 'unknown'),
                'total' => (float) $order->total,
                'currency' => $order->currency_code,
                'comment' => ($order->comment ?? ''),
            ],
            'promo_code' => $promo_usage === null && $promo_total === null ? null : [
                'code' => $promo_usage?->promoCode?->code,
                'discount_type' => $promo_usage?->discount_type?->value,
                'promo_type' => $promo_usage?->promo_type?->value,
                'discount_amount' => $promo_total === null ? 0.0 : abs((float) $promo_total->value),
            ],
            'products' => $order->products->map(fn ($product): array => [
                'id' => integer_value($product->getKey()),
                'product_id' => integer_value($product->product_id),
                'variant_id' => integer_value($product->product_variant_id),
                'name' => (string) $product->name,
                'model' => $product->model,
                'sku' => $product->sku,
                'ean' => $product->ean,
                'quantity' => (int) $product->quantity,
                'unit_price' => (float) $product->unit_price,
                'line_total' => (float) $product->line_total,
            ])->values()->all(),
            'shipping' => [
                'method' => $order->shipping?->method,
                'code' => $order->shipping?->code,
                'city' => $order->shipping?->city,
                'address' => $order->shipping?->address,
                'delivery_point' => $order->shipping?->delivery_point,
                'postcode' => $order->shipping?->postcode,
            ],
            'payment' => [
                'method' => $payment?->method,
                'code' => $payment?->code,
                'status' => $payment?->paymentStatus?->code,
                'amount' => $payment === null ? null : (float) $payment->amount,
                'transaction_id' => $payment?->transaction_id,
            ],
            'customer' => [
                'first_name' => $order->customer?->first_name,
                'last_name' => $order->customer?->last_name,
                'phone' => $order->customer?->telephone,
                'email' => $order->customer?->email,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function formatText(array $payload): string
    {
        $order = array_value(Arr::get($payload, 'order', []));
        $customer = array_value(Arr::get($payload, 'customer', []));
        $payment = array_value(Arr::get($payload, 'payment', []));
        $promo = Arr::get($payload, 'promo_code');
        $products = array_value(Arr::get($payload, 'products', []));
        $lines = [
            'Order: ' . string_value(Arr::get($order, 'number', '—')),
            'Status: ' . string_value(Arr::get($order, 'status', '—')),
            'Total: ' . string_value(Arr::get($order, 'total', '0')) . ' ' . string_value(Arr::get($order, 'currency', '')),
            'Payment: ' . string_value(Arr::get($payment, 'status', '—')),
            'Customer: ' . Str::squish(string_value(Arr::get($customer, 'first_name', '')) . ' ' . string_value(Arr::get($customer, 'last_name', ''))),
        ];

        if (is_array($promo)) {
            $promo_data = array_value($promo);
            $lines[] = 'Promo code: ' . string_value(Arr::get($promo_data, 'code', '—'));
            $lines[] = 'Promo discount: ' . string_value(Arr::get($promo_data, 'discount_amount', '0'));
        }

        $lines[] = 'Products:';
        foreach ($products as $product) {
            if (is_array($product)) {
                $lines[] = '- ' . string_value(Arr::get($product, 'name', '—')) . ' x' . string_value(Arr::get($product, 'quantity', 0)) . ': ' . string_value(Arr::get($product, 'line_total', 0));
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
