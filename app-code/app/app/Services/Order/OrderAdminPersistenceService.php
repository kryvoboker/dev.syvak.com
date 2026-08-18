<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\User;
use App\Supports\Services\CacheInvalidationService;
use App\Supports\Services\Currency\ConvertPrice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class OrderAdminPersistenceService
{
    public function __construct(
        private OrderLifecycleService $order_lifecycle_service,
        private OrderAdminDeliveryService $order_admin_delivery_service,
        private OrderAdminOptionsService $order_admin_options_service,
        private ConvertPrice $convert_price,
        private CacheInvalidationService $cache_invalidation_service,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     * @throws Throwable
     */
    public function update(Orders $order, array $data): Orders
    {
        return DB::transaction(function () use ($order, $data): Orders {
            $order->loadMissing([
                'status',
                'customer',
                'shipping',
                'payments.paymentStatus',
                'products',
                'totals',
            ]);

            $old_order_status_id = $order->order_status_id;
            $before_snapshot = $this->captureAuditSnapshot($order);
            $changed_sections = [];
            $currency_changed = $this->updateCurrency($order, $data, $changed_sections);

            if (array_key_exists('comment', $data) && $order->comment !== $data['comment']) {
                $order->comment = $this->nullableString($data['comment']);
                $changed_sections[] = 'order';
            }

            $this->updateCustomer($order, $this->stringKeyedArray($data['customer'] ?? []), $changed_sections);
            $this->updateShipping($order, $this->stringKeyedArray($data['shipping'] ?? []), $changed_sections);
            $this->updateShippingCost($order, $data, $changed_sections);
            $this->updateProducts($order, $this->listArray($data['products'] ?? []), $changed_sections, $currency_changed);
            $this->updatePayments($order, $this->listArray($data['payments'] ?? []), $changed_sections);

            $this->recalculateOrderTotal($order);

            if ($order->isDirty()) {
                $order->save();
                $changed_sections[] = 'order';
            }

            $changed_sections = array_values(array_unique($changed_sections));

            if ($changed_sections !== []) {
                $after_snapshot = $this->captureAuditSnapshot($order);
                $this->createAuditHistory($order, $before_snapshot, $after_snapshot, $old_order_status_id);

                Log::channel('daily')->info('[OrderAdminPersistenceService] order updated by admin', [
                    'order_id' => $order->getKey(),
                    'order_number' => $order->order_number,
                    'admin_user_id' => auth()->id(),
                    'changed_sections' => $changed_sections,
                    'order_status_id' => $order->order_status_id,
                ]);
                $this->cache_invalidation_service->flushAfterCommit('admin_order_updated');
            }

            $fresh_order = $order->fresh([
                'status.descriptions',
                'customer',
                'shipping',
                'payments.paymentStatus.descriptions',
                'products',
                'totals',
                'histories',
            ]);

            if (! $fresh_order instanceof Orders) {
                throw new \LogicException('Updated order could not be reloaded.');
            }

            return $fresh_order;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $changed_sections
     */
    private function updateCurrency(Orders $order, array $data, array &$changed_sections): bool
    {
        $currency_id = $this->nullableInteger(Arr::get($data, 'currency_id'));

        if ($currency_id === null) {
            return false;
        }

        $target_currency = Currency::query()
            ->whereKey($currency_id)
            ->where('is_active', true)
            ->firstOrFail();
        $source_currency = Currency::query()
            ->whereKey($order->currency_id)
            ->where('is_active', true)
            ->first();
        $source_exchange_rate = (float) $order->exchange_rate;

        if ($source_exchange_rate <= 0 && $source_currency instanceof Currency) {
            $source_exchange_rate = (float) $source_currency->exchange_rate;
        }

        if ($currency_id === (int) $order->currency_id) {
            if ((float) $order->exchange_rate <= 0 && $source_exchange_rate > 0) {
                $order->exchange_rate = $source_exchange_rate;
                $changed_sections[] = 'currency';
            }

            return false;
        }

        $target_exchange_rate = (float) $target_currency->exchange_rate;
        $target_decimal_places = (int) $target_currency->decimal_places;

        foreach ($order->products as $product) {
            foreach (['unit_price', 'discount', 'line_total'] as $field) {
                $product->{$field} = $this->convert_price->convertUsingExchangeRates(
                    (float) $product->{$field},
                    $source_exchange_rate,
                    $target_exchange_rate,
                    $target_decimal_places,
                );
            }

            $product->save();
        }

        foreach ($order->payments as $payment) {
            $payment->amount = $this->convert_price->convertUsingExchangeRates(
                (float) $payment->amount,
                $source_exchange_rate,
                $target_exchange_rate,
                $target_decimal_places,
            );
            $payment->save();
        }

        foreach ($order->totals as $total) {
            $total->value = $this->convert_price->convertUsingExchangeRates(
                (float) $total->value,
                $source_exchange_rate,
                $target_exchange_rate,
                $target_decimal_places,
            );
            $total->save();
        }

        $order->fill([
            'currency_id' => $target_currency->getKey(),
            'currency_code' => $target_currency->code,
            'exchange_rate' => $target_currency->exchange_rate,
        ]);
        $order->total = $this->convert_price->convertUsingExchangeRates(
            (float) $order->total,
            $source_exchange_rate,
            $target_exchange_rate,
            $target_decimal_places,
        );
        $changed_sections[] = 'currency';

        Log::channel('daily')->info('[OrderAdminPersistenceService] order currency converted', [
            'order_id' => $order->getKey(),
            'currency_code' => $target_currency->code,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $changed_sections
     */
    private function updateCustomer(Orders $order, array $data, array &$changed_sections): void
    {
        if ($data === [] || ! $order->customer) {
            return;
        }

        $customer = $order->customer;
        $user_id = $this->nullableInteger(Arr::get($data, 'user_id', $customer->user_id));
        $user = $user_id !== null ? User::query()->find($user_id) : null;
        $user_group_id = $customer->user_group_id;

        if ($user instanceof User) {
            $user_group_id = $user->user_group_id;
        }

        $customer->fill([
            'user_id' => $user_id,
            'user_group_id' => $user_group_id,
            'first_name' => $this->stringValue(Arr::get($data, 'first_name', $customer->first_name)),
            'last_name' => $this->stringValue(Arr::get($data, 'last_name', $customer->last_name)),
            'email' => $this->nullableString(Arr::get($data, 'email', $customer->email)),
            'telephone' => $this->stringValue(Arr::get($data, 'telephone', $customer->telephone)),
        ]);

        if ($customer->isDirty()) {
            $customer->save();
            $changed_sections[] = 'customer';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $changed_sections
     */
    private function updateShipping(Orders $order, array $data, array &$changed_sections): void
    {
        if ($data === [] || ! $order->shipping) {
            return;
        }

        $shipping = $order->shipping;
        $method_code = $this->nullableString(Arr::get($data, 'code', $shipping->code));
        $method_options = $this->order_admin_options_service->getDeliveryMethodOptions();
        $capabilities = $this->order_admin_delivery_service->getCapabilities($method_code);
        $city_id = $capabilities['city']
            ? $this->nullableString(Arr::get($data, 'city_id', $shipping->city_id))
            : null;
        $city = $city_id !== null
            ? $this->order_admin_delivery_service->findCity($method_code ?? '', $city_id)
            : null;
        $delivery_point_id = $capabilities['delivery_point']
            ? $this->nullableString(Arr::get($data, 'delivery_point_id', $shipping->delivery_point_id))
            : null;
        $delivery_point = $delivery_point_id !== null && $city_id !== null
            ? $this->order_admin_delivery_service->findDeliveryPoint($method_code ?? '', $city_id, $delivery_point_id)
            : null;
        $provider_data = [];

        if ($city !== null) {
            $provider_data['city'] = $city['provider_data'];
        }

        if ($delivery_point !== null) {
            $provider_data['delivery_point'] = $delivery_point['provider_data'];
        }

        $shipping->fill([
            'method' => $method_options[$method_code ?? ''] ?? $shipping->method,
            'code' => $method_code,
            'is_cost_enabled' => $method_code !== 'pickup_store'
                && (bool) Arr::get($data, 'is_cost_enabled', $shipping->is_cost_enabled),
            'city' => $city['name'] ?? null,
            'city_id' => $city !== null ? $city_id : null,
            'address' => $capabilities['courier_address']
                ? $this->nullableString(Arr::get($data, 'address'))
                : null,
            'delivery_point' => $delivery_point['name'] ?? null,
            'delivery_point_id' => $delivery_point !== null ? $delivery_point_id : null,
            'postcode' => $delivery_point['postcode'] ?? null,
            'provider_data' => $provider_data !== [] ? $provider_data : null,
        ]);

        if ($shipping->isDirty()) {
            $shipping->save();
            $changed_sections[] = 'shipping';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $changed_sections
     */
    private function updateShippingCost(Orders $order, array $data, array &$changed_sections): void
    {
        $shipping = $order->shipping;

        if ($shipping === null) {
            return;
        }

        $shipping_code = (string) $shipping->code;
        $shipping_cost = $this->nullableFloat(Arr::get(
            $data,
            'shipping_cost',
            $this->order_admin_delivery_service->getDefaultDeliveryCost($shipping_code),
        ));
        $is_cost_enabled = $shipping_code !== 'pickup_store'
            && (bool) Arr::get($data, 'shipping_cost_enabled', $shipping->is_cost_enabled);

        if ($shipping_cost === null) {
            return;
        }

        if ($shipping->is_cost_enabled !== $is_cost_enabled) {
            $shipping->is_cost_enabled = $is_cost_enabled;
            $shipping->save();
            $changed_sections[] = 'shipping';
        }

        $shipping_total = $order->totals()->where('total_type', 'shipping')->first();

        if ($shipping_total === null) {
            $order->totals()->create([
                'total_type' => 'shipping',
                'name' => __('admin/orders/orders.labels.shipping_cost'),
                'value' => $shipping_cost,
                'sort_order' => $this->integerValue($order->totals()->max('sort_order')) + 1,
            ]);
            $changed_sections[] = 'shipping';

            return;
        }

        if ((float) $shipping_total->value === $shipping_cost) {
            return;
        }

        $shipping_total->value = $shipping_cost;
        $shipping_total->save();
        $changed_sections[] = 'shipping';
    }

    /**
     * @param  array<int, mixed>  $products
     * @param  array<int, string>  $changed_sections
     */
    private function updateProducts(
        Orders $order,
        array $products,
        array &$changed_sections,
        bool $currency_changed = false,
    ): void {
        /** @var array<int, int> $submitted_product_ids */
        $submitted_product_ids = [];

        foreach ($products as $product_data) {
            if (! is_array($product_data)) {
                continue;
            }

            $order_product_id = $this->nullableInteger($product_data['id'] ?? null);
            $product_id = $this->nullableInteger($product_data['product_id'] ?? null);

            if ($order_product_id !== null) {
                $submitted_product_ids[] = $order_product_id;
            }

            $order_product = $order_product_id !== null
                ? $order->products()->whereKey($order_product_id)->first()
                : null;

            if (! $order_product && $product_id !== null) {
                $snapshot = $this->resolveProductSnapshot($product_id);
                $quantity = max(1, $this->integerValue($product_data['quantity'] ?? 1));
                $discount = $this->nullableFloat($product_data['discount'] ?? 0) ?? 0;
                $unit_price = $this->floatValue($snapshot['unit_price'] ?? 0);

                $new_order_product = $order->products()->create([
                    ...$snapshot,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'unit_price' => $unit_price,
                    'line_total' => max(0, round($quantity * $unit_price - $discount, 4)),
                ]);
                $submitted_product_ids[] = $new_order_product->getKey();
                $changed_sections[] = 'products';

                continue;
            }

            if (! $order_product) {
                continue;
            }

            $is_product_replaced = $product_id !== null && (int) $product_id !== (int) $order_product->product_id;
            $snapshot = $is_product_replaced
                ? $this->resolveProductSnapshot($product_id)
                : [
                    'product_id' => $order_product->product_id,
                    'product_variant_id' => $order_product->product_variant_id,
                    'is_default_variant' => $order_product->is_default_variant,
                    'name' => $order_product->name,
                    'model' => $order_product->model,
                    'sku' => $order_product->sku,
                    'ean' => $order_product->ean,
                    'unit_price' => $order_product->unit_price,
                ];

            $quantity = max(1, $this->integerValue($product_data['quantity'] ?? $order_product->quantity));
            $discount = $this->nullableFloat($product_data['discount'] ?? $order_product->discount) ?? 0;
            $unit_price = ($currency_changed || ! $is_product_replaced)
                && is_numeric($product_data['unit_price'] ?? null)
                ? $this->floatValue($product_data['unit_price'])
                : $this->floatValue($snapshot['unit_price'] ?? $order_product->unit_price);

            $order_product->fill([
                ...$snapshot,
                'quantity' => $quantity,
                'discount' => $discount,
                'unit_price' => $unit_price,
                'line_total' => max(0, round($quantity * $unit_price - $discount, 4)),
            ]);

            if ($order_product->isDirty()) {
                $order_product->save();
                $changed_sections[] = 'products';
            }
        }

        $existing_product_ids = $order->products()->pluck('id')->all();
        $removed_product_ids = array_diff(
            array_map(fn (mixed $id): int => $this->integerValue($id), $existing_product_ids),
            array_map(fn (mixed $id): int => $this->integerValue($id), $submitted_product_ids),
        );

        if ($removed_product_ids !== []) {
            $order->products()->whereKey($removed_product_ids)->delete();
            $changed_sections[] = 'products';
        }
    }

    /**
     * @param  array<int, mixed>  $payments
     * @param  array<int, string>  $changed_sections
     */
    private function updatePayments(Orders $order, array $payments, array &$changed_sections): void
    {
        foreach ($payments as $payment_data) {
            if (! is_array($payment_data) || ! is_numeric($payment_data['id'] ?? null)) {
                continue;
            }

            $payment = $order->payments()->whereKey((int) $payment_data['id'])->first();

            if (! $payment instanceof OrderPayments) {
                continue;
            }

            $payment->fill([
                'method' => $this->resolvePaymentMethodName(
                    $this->nullableString(Arr::get($payment_data, 'code', $payment->code)),
                    $payment->method,
                ),
                'code' => $this->nullableString(Arr::get($payment_data, 'code', $payment->code)),
                'transaction_id' => $this->nullableString(Arr::get($payment_data, 'transaction_id', $payment->transaction_id)),
                'amount' => $this->nullableFloat(Arr::get($payment_data, 'amount', $payment->amount)) ?? 0,
                'failure_reason' => $this->nullableString(Arr::get($payment_data, 'failure_reason', $payment->failure_reason)),
            ]);

            $payment->save();

            $payment_status_id = Arr::get($payment_data, 'payment_status_id');

            if (is_numeric($payment_status_id) && (int) $payment_status_id !== (int) $payment->payment_status_id) {
                $status = PaymentStatuses::query()->findOrFail((int) $payment_status_id);
                $this->order_lifecycle_service->transitionPayment(
                    $payment,
                    (string) $status->code,
                    [],
                    $payment->failure_reason,
                );
                $this->order_lifecycle_service->transitionOrderForPayment($order, (string) $status->code, [
                    'source' => 'admin_order_update',
                    'payment_id' => $payment->getKey(),
                ]);
                $changed_sections[] = 'payment_status';
            }

            if ($payment->wasChanged()) {
                $changed_sections[] = 'payments';
            }
        }
    }

    private function recalculateOrderTotal(Orders $order): void
    {
        $items_subtotal = (float) $order->products()->sum('line_total');
        $order->totals()
            ->where('total_type', 'sub_total')
            ->update(['value' => $items_subtotal]);

        $additional_totals = (float) $order->totals()
            ->whereNotIn('total_type', ['sub_total', 'total', 'shipping'])
            ->sum('value');
        $shipping_total = (float) $order->totals()
            ->where('total_type', 'shipping')
            ->sum('value');
        $shipping_is_enabled = (bool) $order->shipping?->is_cost_enabled;
        $grand_total = $items_subtotal + $additional_totals
            + ($shipping_is_enabled ? $shipping_total : 0.0);

        $total = $order->totals()->where('total_type', 'total')->first();

        if ($total === null) {
            $order->totals()->create([
                'total_type' => 'total',
                'name' => __('admin/orders/orders.labels.order_total'),
                'value' => $grand_total,
                'sort_order' => $this->integerValue($order->totals()->max('sort_order')) + 1,
            ]);
        } else {
            $total->value = $grand_total;
            $total->save();
        }

        $order->total = $grand_total;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveProductSnapshot(int $product_id): array
    {
        $language_id = Language::query()
            ->where('code', app()->getLocale())
            ->value('id');
        $product = Product::query()
            ->with([
                'defaultVariant',
                'productDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->find($product_id);

        if (! $product instanceof Product) {
            throw (new ModelNotFoundException())->setModel(Product::class, [$product_id]);
        }

        $description = $product->productDescription->first();
        $variant = $product->defaultVariant;
        $variant_id = null;
        $is_default_variant = false;
        $unit_price = (float) ($product->price ?? 0);

        if ($variant instanceof ProductVariant) {
            $variant_id = $variant->getKey();
            $is_default_variant = $variant->is_default;
            $unit_price = (float) $variant->price;
        }

        return [
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant_id,
            'is_default_variant' => $is_default_variant,
            'name' => $description?->name ?: $product->model ?: $product->sku ?: $this->stringValue($product->getKey()),
            'model' => $product->model,
            'sku' => $product->sku,
            'ean' => $product->ean,
            'unit_price' => $unit_price,
        ];
    }

    private function resolvePaymentMethodName(?string $payment_code, ?string $fallback): ?string
    {
        if ($payment_code === null) {
            return $fallback;
        }

        return $this->order_admin_options_service->getPaymentMethodOptions(
            app()->getLocale(),
            $payment_code,
        )[$payment_code] ?? $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = Str::trim($this->stringValue($value));

        return $value !== '' ? $value : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) && $this->integerValue($value) > 0 ? $this->integerValue($value) : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function stringKeyedArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @return array<int, mixed>
     */
    private function listArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function captureAuditSnapshot(Orders $order): array
    {
        return [
            'order' => [
                'comment' => $order->comment,
                'total' => (float) $order->total,
                'currency_id' => $order->currency_id,
                'currency_code' => $order->currency_code,
                'exchange_rate' => (float) $order->exchange_rate,
                'order_status_id' => $order->order_status_id,
            ],
            'customer' => $order->customer?->only([
                'first_name',
                'last_name',
                'email',
                'telephone',
            ]),
            'shipping' => $order->shipping?->only([
                'method',
                'code',
                'is_cost_enabled',
                'city',
                'address',
                'delivery_point',
                'postcode',
            ]),
            'payments' => $order->payments()->with('paymentStatus')->get()->mapWithKeys(fn (OrderPayments $payment): array => [
                $this->stringValue($payment->getKey()) => [
                    'method' => $payment->method,
                    'code' => $payment->code,
                    'payment_status_id' => $payment->payment_status_id,
                    'payment_status' => $payment->paymentStatus?->code,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => (float) $payment->amount,
                    'failure_reason' => $payment->failure_reason,
                ],
            ])->all(),
            'products' => $order->products()->get()->mapWithKeys(fn ($product): array => [
                $this->stringValue($product->getKey()) => $product->only([
                    'id',
                    'product_id',
                    'product_variant_id',
                    'name',
                    'model',
                    'sku',
                    'ean',
                    'quantity',
                    'discount',
                    'unit_price',
                    'line_total',
                ]),
            ])->all(),
            'totals' => $order->totals()->get()->mapWithKeys(fn ($total): array => [
                $this->stringValue($total->getKey()) => [
                    'total_type' => $this->stringValue($total->getRawOriginal('total_type')),
                    'name' => $total->name,
                    'value' => (float) $total->value,
                ],
            ])->all(),
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function createAuditHistory(
        Orders $order,
        array $before,
        array $after,
        ?int $old_order_status_id,
    ): void {
        $entries = [];
        $before_order = (array) $before['order'];
        $after_order = (array) $after['order'];

        if ($before_order['currency_id'] !== $after_order['currency_id']) {
            $entries[] = [
                'event' => 'admin_order_currency_changed',
                'json' => $this->historyData([
                    'old_currency' => $before_order['currency_code'],
                    'new_currency' => $after_order['currency_code'],
                    'old_exchange_rate' => $before_order['exchange_rate'],
                    'new_exchange_rate' => $after_order['exchange_rate'],
                ]),
            ];
        }

        $this->appendFieldChange(
            $entries,
            'admin_order_comment_changed',
            'comment',
            $before_order['comment'],
            $after_order['comment'],
        );

        $this->appendEntityChange(
            $entries,
            'admin_order_customer_updated',
            $this->stringKeyedArray($before['customer'] ?? []),
            $this->stringKeyedArray($after['customer'] ?? []),
            [
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
                'telephone' => 'telephone',
            ],
        );

        $before_shipping = (array) $before['shipping'];
        $after_shipping = (array) $after['shipping'];
        $shipping_method_changed = $before_shipping['code'] !== ($after_shipping['code'] ?? null);

        if ($shipping_method_changed) {
            $entries[] = [
                'event' => 'admin_order_shipping_method_changed',
                'json' => $this->historyData([
                    'old_method' => $before_shipping['method'],
                    'new_method' => $after_shipping['method'],
                    'old_code' => $before_shipping['code'],
                    'new_code' => $after_shipping['code'],
                ]),
            ];
        }

        $this->appendEntityChange(
            $entries,
            'admin_order_shipping_updated',
            $this->stringKeyedArray($before_shipping),
            $this->stringKeyedArray($after_shipping),
            [
                'city' => 'city',
                'address' => 'address',
                'delivery_point' => 'delivery_point',
                'postcode' => 'postcode',
            ],
        );

        $before_shipping_cost = $this->getHistoryTotalValue($before, 'shipping');
        $after_shipping_cost = $this->getHistoryTotalValue($after, 'shipping');

        if (
            ($before_shipping['is_cost_enabled'] ?? null) !== ($after_shipping['is_cost_enabled'] ?? null)
            || $before_shipping_cost !== $after_shipping_cost
        ) {
            $entries[] = [
                'event' => 'admin_order_shipping_cost_changed',
                'json' => $this->historyData([
                    'cost_enabled' => $after_shipping['is_cost_enabled'],
                    'old_cost' => $before_shipping_cost,
                    'new_cost' => $after_shipping_cost,
                ]),
            ];
        }

        $before_payments = (array) $before['payments'];
        $after_payments = (array) $after['payments'];

        foreach ($after_payments as $payment_id => $payment) {
            $old_payment = $this->stringKeyedArray($before_payments[$payment_id] ?? []);

            if ($old_payment === []) {
                $entries[] = [
                    'event' => 'admin_order_payment_added',
                    'json' => $this->historyData($this->stringKeyedArray($payment)),
                ];

                continue;
            }

            $this->appendEntityChange(
                $entries,
                'admin_order_payment_updated',
                $old_payment,
                $this->stringKeyedArray($payment),
                [
                    'method' => 'method',
                    'code' => 'code',
                    'payment_status' => 'payment_status',
                    'transaction_id' => 'transaction_id',
                    'amount' => 'amount',
                    'failure_reason' => 'failure_reason',
                ],
            );
        }

        foreach (array_diff_key($before_payments, $after_payments) as $payment) {
            $entries[] = [
                'event' => 'admin_order_payment_removed',
                'json' => $this->historyData($this->stringKeyedArray($payment)),
            ];
        }

        $before_products = (array) $before['products'];
        $after_products = (array) $after['products'];

        foreach ($after_products as $product_id => $product) {
            if (! isset($before_products[$product_id])) {
                $entries[] = [
                    'event' => 'admin_order_product_added',
                    'json' => $this->historyData($this->stringKeyedArray($product)),
                ];

                continue;
            }

            $this->appendEntityChange(
                $entries,
                'admin_order_product_updated',
                $this->stringKeyedArray($before_products[$product_id]),
                $this->stringKeyedArray($product),
                [
                    'name' => 'name',
                    'model' => 'model',
                    'sku' => 'sku',
                    'ean' => 'ean',
                    'quantity' => 'quantity',
                    'discount' => 'discount',
                    'unit_price' => 'unit_price',
                    'line_total' => 'line_total',
                ],
                [
                    'id' => $this->stringKeyedArray($product)['id'] ?? $product_id,
                    'product_id' => $this->stringKeyedArray($product)['product_id'] ?? null,
                    'product_variant_id' => $this->stringKeyedArray($product)['product_variant_id'] ?? null,
                    'model' => $this->stringKeyedArray($product)['model'] ?? null,
                    'sku' => $this->stringKeyedArray($product)['sku'] ?? null,
                    'ean' => $this->stringKeyedArray($product)['ean'] ?? null,
                    'name' => $this->stringKeyedArray($product)['name'] ?? null,
                ],
            );
        }

        foreach (array_diff_key($before_products, $after_products) as $product) {
            $entries[] = [
                'event' => 'admin_order_product_removed',
                'json' => $this->historyData($this->stringKeyedArray($product)),
            ];
        }

        if ($before['totals'] !== $after['totals']) {
            $entries[] = [
                'event' => 'admin_order_totals_recalculated',
                'json' => $this->historyData([
                    'old_totals' => $this->formatHistoryTotals($this->stringKeyedArray($before['totals'] ?? [])),
                    'new_totals' => $this->formatHistoryTotals($this->stringKeyedArray($after['totals'] ?? [])),
                    'old_order_total' => $before_order['total'],
                    'new_order_total' => $after_order['total'],
                ]),
            ];
        }

        foreach ($entries as $entry) {
            $order->histories()->create([
                'user_id' => auth()->id(),
                'old_order_status_id' => $old_order_status_id,
                'order_status_id' => $order->order_status_id,
                'event' => $entry['event'],
                'json' => [
                    ...$entry['json'],
                    ...$this->getHistoryActorData(),
                ],
            ]);
        }
    }

    /**
     * @param array<int, array{event: string, json: array<string, mixed>}> $entries
     */
    private function appendFieldChange(
        array &$entries,
        string $event,
        string $field,
        mixed $old_value,
        mixed $new_value,
    ): void {
        if ($old_value === $new_value) {
            return;
        }

        $entries[] = [
            'event' => $event,
            'json' => $this->historyData([
                "old_{$field}" => $old_value,
                "new_{$field}" => $new_value,
            ]),
        ];
    }

    /**
     * @param array<int, array{event: string, json: array<string, mixed>}> $entries
     * @param array<string, mixed> $old_values
     * @param array<string, mixed> $new_values
     * @param array<string, string> $fields
     * @param array<string, mixed> $context
     */
    private function appendEntityChange(
        array &$entries,
        string $event,
        array $old_values,
        array $new_values,
        array $fields,
        array $context = [],
    ): void {
        $changes = [];

        foreach ($fields as $field => $label_key) {
            if (($old_values[$field] ?? null) !== ($new_values[$field] ?? null)) {
                $changes["old_{$label_key}"] = $old_values[$field] ?? null;
                $changes["new_{$label_key}"] = $new_values[$field] ?? null;
            }
        }

        if ($changes !== []) {
            $entries[] = [
                'event' => $event,
                'json' => $this->historyData([...$context, ...$changes]),
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function historyData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $translation_key = 'admin/orders/orders.history_data.' . Str::snake((string) $key);
            $translated_label = __($translation_key);
            $label = $translated_label !== $translation_key
                ? $translated_label
                : Str::headline((string) $key);
            $result[$label] = $this->formatHistoryValue($value);
        }

        return $result;
    }

    private function formatHistoryValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value
                ? __('admin/orders/orders.history_data.yes')
                : __('admin/orders/orders.history_data.no');
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return $this->stringValue(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $this->stringValue($value);
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function getHistoryTotalValue(array $snapshot, string $type): ?float
    {
        foreach ($this->stringKeyedArray($snapshot['totals'] ?? []) as $total) {
            $total = $this->stringKeyedArray($total);
            if (($total['total_type'] ?? null) === $type) {
                return $this->floatValue($total['value'] ?? null);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $totals
     */
    private function formatHistoryTotals(array $totals): string
    {
        return collect($totals)
            ->map(fn (mixed $total): string => $this->formatHistoryTotal($total))
            ->implode('; ');
    }

    private function formatHistoryTotal(mixed $total): string
    {
        $total = $this->stringKeyedArray($total);

        return sprintf(
            '%s: %s',
            $this->stringValue($total['name'] ?? $total['total_type'] ?? ''),
            format_price($this->floatValue($total['value'] ?? 0)),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getHistoryActorData(): array
    {
        $user = auth()->user();
        $roles = $user?->getRoleNames()->implode(', ');

        return [
            __('admin/orders/orders.history_data.actor') => $user !== null
                ? __('admin/orders/orders.history_data.administrator')
                : __('admin/orders/orders.history_data.system'),
            __('admin/orders/orders.history_data.roles') => is_string($roles) && $roles !== '' ? $roles : '—',
        ];
    }
}
