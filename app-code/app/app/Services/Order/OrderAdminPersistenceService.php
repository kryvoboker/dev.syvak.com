<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class OrderAdminPersistenceService
{
    public function __construct(
        private OrderLifecycleService $order_lifecycle_service,
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
            $changed_sections = [];

            if (array_key_exists('comment', $data) && $order->comment !== $data['comment']) {
                $order->comment = $this->nullableString($data['comment']);
                $changed_sections[] = 'order';
            }

            $this->updateOrderStatus($order, $data, $changed_sections);
            $this->updateCustomer($order, (array) ($data['customer'] ?? []), $changed_sections);
            $this->updateShipping($order, (array) ($data['shipping'] ?? []), $changed_sections);
            $this->updateProducts($order, (array) ($data['products'] ?? []), $changed_sections);
            $this->updateTotals($order, (array) ($data['totals'] ?? []), $changed_sections);
            $this->updatePayments($order, (array) ($data['payments'] ?? []), $changed_sections);

            $this->recalculateOrderTotal($order);

            if ($order->isDirty()) {
                $order->save();
                $changed_sections[] = 'order';
            }

            $changed_sections = array_values(array_unique($changed_sections));

            if ($changed_sections !== []) {
                $order->histories()->create([
                    'user_id' => auth()->id(),
                    'old_order_status_id' => $old_order_status_id,
                    'order_status_id' => $order->order_status_id,
                    'event' => 'admin_order_updated',
                    'json' => [
                        'sections' => $changed_sections,
                        'admin_user_id' => auth()->id(),
                    ],
                ]);

                Log::channel('daily')->info('[OrderAdminPersistenceService] order updated by admin', [
                    'order_id' => $order->getKey(),
                    'order_number' => $order->order_number,
                    'admin_user_id' => auth()->id(),
                    'changed_sections' => $changed_sections,
                    'order_status_id' => $order->order_status_id,
                ]);
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
    private function updateOrderStatus(Orders $order, array $data, array &$changed_sections): void
    {
        $status_id = Arr::get($data, 'order_status_id');

        if (! is_numeric($status_id) || (int) $status_id === (int) $order->order_status_id) {
            return;
        }

        $status = OrderStatuses::query()->findOrFail((int) $status_id);

        $this->order_lifecycle_service->transitionOrder($order, $status, 'admin_order_status_changed');
        $order->order_status_name = $this->resolveStatusName($status);
        $changed_sections[] = 'order_status';
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
            'first_name' => (string) Arr::get($data, 'first_name', $customer->first_name),
            'last_name' => (string) Arr::get($data, 'last_name', $customer->last_name),
            'email' => $this->nullableString(Arr::get($data, 'email', $customer->email)),
            'telephone' => (string) Arr::get($data, 'telephone', $customer->telephone),
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
        $shipping->fill([
            'method' => $this->nullableString(Arr::get($data, 'method', $shipping->method)),
            'code' => $this->nullableString(Arr::get($data, 'code', $shipping->code)),
            'city' => $this->nullableString(Arr::get($data, 'city', $shipping->city)),
            'city_id' => $this->nullableString(Arr::get($data, 'city_id', $shipping->city_id)),
            'address' => $this->nullableString(Arr::get($data, 'address', $shipping->address)),
            'delivery_point' => $this->nullableString(Arr::get($data, 'delivery_point', $shipping->delivery_point)),
            'delivery_point_id' => $this->nullableString(Arr::get($data, 'delivery_point_id', $shipping->delivery_point_id)),
            'postcode' => $this->nullableString(Arr::get($data, 'postcode', $shipping->postcode)),
        ]);

        if ($shipping->isDirty()) {
            $shipping->save();
            $changed_sections[] = 'shipping';
        }
    }

    /**
     * @param  array<int, mixed>  $products
     * @param  array<int, string>  $changed_sections
     */
    private function updateProducts(Orders $order, array $products, array &$changed_sections): void
    {
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
                $quantity = max(1, (int) ($product_data['quantity'] ?? 1));
                $discount = $this->nullableFloat($product_data['discount'] ?? 0) ?? 0;
                $unit_price = (float) ($snapshot['unit_price'] ?? 0);

                $order->products()->create([
                    ...$snapshot,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'unit_price' => $unit_price,
                    'line_total' => max(0, round($quantity * $unit_price - $discount, 4)),
                ]);
                $changed_sections[] = 'products';

                continue;
            }

            if (! $order_product) {
                continue;
            }

            $snapshot = $product_id !== null
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

            $quantity = max(1, (int) ($product_data['quantity'] ?? $order_product->quantity));
            $discount = $this->nullableFloat($product_data['discount'] ?? $order_product->discount) ?? 0;
            $unit_price = (float) ($snapshot['unit_price'] ?? $order_product->unit_price);

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
        $removed_product_ids = array_diff($existing_product_ids, $submitted_product_ids);

        if ($removed_product_ids !== []) {
            $order->products()->whereKey($removed_product_ids)->delete();
            $changed_sections[] = 'products';
        }
    }

    /**
     * @param  array<int, mixed>  $totals
     * @param  array<int, string>  $changed_sections
     */
    private function updateTotals(Orders $order, array $totals, array &$changed_sections): void
    {
        foreach ($totals as $total_data) {
            if (! is_array($total_data) || ! is_numeric($total_data['id'] ?? null)) {
                continue;
            }

            $total = $order->totals()->whereKey((int) $total_data['id'])->first();

            if (! $total) {
                continue;
            }

            $total->fill([
                'name' => (string) Arr::get($total_data, 'name', $total->name),
                'value' => $this->nullableFloat(Arr::get($total_data, 'value', $total->value)) ?? 0,
                'sort_order' => max(1, (int) Arr::get($total_data, 'sort_order', $total->sort_order)),
            ]);

            if ($total->isDirty()) {
                $total->save();
                $changed_sections[] = 'totals';
            }
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
                'method' => $this->nullableString(Arr::get($payment_data, 'method', $payment->method)),
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

        $grand_total = $items_subtotal + (float) $order->totals()
            ->whereNotIn('total_type', ['sub_total', 'total'])
            ->sum('value');

        $order->totals()
            ->where('total_type', 'total')
            ->update(['value' => $grand_total]);
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
                'productDescription' => fn ($query) => $query->where('language_id', $language_id),
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
            'name' => $description?->name ?: $product->model ?: $product->sku ?: (string) $product->getKey(),
            'model' => $product->model,
            'sku' => $product->sku,
            'ean' => $product->ean,
            'unit_price' => $unit_price,
        ];
    }

    private function resolveStatusName(object $status): string
    {
        $language_id = Language::query()
            ->where('code', app()->getLocale())
            ->value('id');
        $description = $status->descriptions
            ->first(fn ($item): bool => (int) $item->language_id === (int) $language_id);

        return (string) ($description?->name ?: $status->code);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
