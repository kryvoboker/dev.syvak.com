<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\TotalTypesEnum;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Models\Orders\OrderCustomers;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderShippings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;

final readonly class OrderAggregatePersistenceService
{
    public function __construct(
        private OrderLifecycleService $order_lifecycle_service,
        private BankTransferConfig $bank_transfer_config,
        private WayForPayConfig $wayforpay_config,
    ) {
    }

    /**
     * @param array<string, mixed> $validated_data
     * @param array<string, mixed> $cart_data
     * @param array<string, mixed> $request_context
     *
     * @throws Throwable
     * @return array{order: Orders, payment: OrderPayments}
     */
    public function createSimpleOrder(
        array  $validated_data,
        array  $cart_data,
        string $locale,
        array  $request_context = [],
    ): array {
        return DB::transaction(function () use ($validated_data, $cart_data, $locale, $request_context): array {
            $language = resolve_language_by_locale($locale);
            $currency = $this->resolveCurrency((string)Arr::get($cart_data, 'totals.currency_code', ''));
            $order_status = $this->order_lifecycle_service->getDefaultOrderStatus();
            $payment_status = $this->order_lifecycle_service->getDefaultPaymentStatus();
            $order_number = (string)Str::ulid();
            $totals = (array)Arr::get($cart_data, 'totals', []);
            $currency_code = $currency instanceof Currency ? $currency->code : '';
            $order_status_name = $order_status
                ->getRelation('descriptions')
                ->first()?->name;

            $order = Orders::query()->create([
                'order_number' => $order_number,
                'order_status_id' => $order_status->getKey(),
                'order_status_name' => $order_status_name,
                'order_type' => Arr::get($validated_data, 'cart_mode', 'regular'),
                'comment' => $this->nullableString(Arr::get($validated_data, 'comment')),
                'total' => (float)Arr::get($totals, 'grand_total', 0),
                'language_id' => $language instanceof Language ? $language->getKey() : null,
                'language_code' => $locale,
                'currency_id' => $currency?->getKey(),
                'currency_code' => (string)Arr::get($totals, 'currency_code', $currency_code),
                'exchange_rate' => (float)Arr::get($totals, 'exchange_rate', 1),
                'accept_language' => $this->nullableString(Arr::get($request_context, 'accept_language')),
                'ip' => (string)Arr::get($request_context, 'ip', '0.0.0.0'),
                'forwarded_ip' => $this->nullableString(Arr::get($request_context, 'forwarded_ip')),
                'user_agent' => $this->nullableString(Arr::get($request_context, 'user_agent')),
                'added_at' => now(),
            ]);

            $this->createCustomer($order, $validated_data);
            $this->createProducts($order, (array)Arr::get($cart_data, 'items', []));
            $this->createTotals($order, $totals);
            $this->createShipping($order, $validated_data, $locale);
            $order->histories()->create([
                'old_order_status_id' => null,
                'order_status_id' => $order_status->getKey(),
                'event' => 'order_created',
                'json' => [],
            ]);

            $payment_code = $this->nullableString(Arr::get($validated_data, 'payment_method'));

            $payment = $order->payments()->create([
                'method' => $this->resolvePaymentMethodName($payment_code, $locale),
                'code' => $payment_code,
                'payment_status_id' => $payment_status->getKey(),
                'amount' => (float)Arr::get($totals, 'grand_total', 0),
            ]);

            $order->load('status');
            $payment->load('paymentStatus');

            return [
                'order' => $order,
                'payment' => $payment,
            ];
        });
    }

    private function resolveCurrency(string $currency_code): ?Currency
    {
        if ($currency_code === '') {
            return new Currency()->getDefaultActiveCurrency();
        }

        return new Currency()->getActiveCurrencyByCode($currency_code)
            ?? new Currency()->getDefaultActiveCurrency();
    }

    /**
     * @param Orders               $order
     * @param array<string, mixed> $validated_data
     *
     * @return OrderCustomers
     */
    private function createCustomer(Orders $order, array $validated_data): OrderCustomers
    {
        $user = auth()->user();

        return $order->customer()->create([
            'user_id' => auth()->id(),
            'user_group_id' => is_object($user) ? $user->user_group_id : null,
            'first_name' => (string)Arr::get($validated_data, 'first_name', ''),
            'last_name' => (string)Arr::get($validated_data, 'last_name', ''),
            'email' => $this->nullableString(Arr::get($validated_data, 'email')),
            'telephone' => clear_telephone((string)Arr::get($validated_data, 'phone', '')),
        ]);
    }

    /**
     * @param array<int, mixed> $items
     */
    private function createProducts(Orders $order, array $items): void
    {
        foreach ($items as $item) {
            $item = is_array($item) ? $item : [];
            $order->products()->create([
                'product_id' => $this->nullableInteger(Arr::get($item, 'product_id')),
                'product_variant_id' => $this->nullableInteger(Arr::get($item, 'variant_id')),
                'is_default_variant' => (bool)Arr::get($item, 'is_default_variant', false),
                'name' => (string)Arr::get($item, 'name', ''),
                'model' => $this->nullableString(Arr::get($item, 'model')),
                'sku' => $this->nullableString(Arr::get($item, 'sku')),
                'ean' => $this->nullableString(Arr::get($item, 'ean')),
                'quantity' => max(1, (int)Arr::get($item, 'quantity', 1)),
                'discount' => $this->nullableFloat(Arr::get($item, 'discount')),
                'unit_price' => (float)Arr::get($item, 'unit_price', 0),
                'line_total' => (float)Arr::get($item, 'line_total', 0),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $totals
     */
    private function createTotals(Orders $order, array $totals): void
    {
        $sort_order = 1;
        $created_total = false;

        foreach ((array)Arr::get($totals, 'lines', []) as $line) {
            $line = is_array($line) ? $line : [];
            $total_type = $this->resolveTotalType((string)Arr::get($line, 'code', ''));

            if ($total_type === null) {
                continue;
            }

            $order->totals()->create([
                'total_type' => $total_type,
                'name' => (string)Arr::get($line, 'label', $total_type->value),
                'value' => (float)Arr::get($line, 'amount', 0),
                'sort_order' => $sort_order++,
            ]);
            $created_total = true;
        }

        if ($created_total) {
            return;
        }

        $order->totals()->create([
            'total_type' => TotalTypesEnum::Total,
            'name' => TotalTypesEnum::Total->value,
            'value' => (float)Arr::get($totals, 'grand_total', 0),
            'sort_order' => 1,
        ]);
    }

    /**
     * @param array<string, mixed> $validated_data
     */
    private function createShipping(Orders $order, array $validated_data, string $locale): OrderShippings
    {
        $city = (array)Arr::get($validated_data, 'city', []);
        $delivery_point = (array)Arr::get($validated_data, 'delivery_point', []);
        $city_id = Arr::get($city, 'nova_poshta_city_id') ?: Arr::get($city, 'ukr_poshta_city_id');
        $delivery_point_id = Arr::get($delivery_point, 'ref') ?: Arr::get($delivery_point, 'id');
        $code = $this->nullableString(Arr::get($validated_data, 'delivery_method'));

        return $order->shipping()->create([
            'method' => $this->resolveDeliveryMethodName($code, $locale),
            'code' => $code,
            'city' => $this->nullableString(Arr::get($city, 'city_description')),
            'city_id' => $this->nullableString($city_id),
            'address' => $this->nullableString(Arr::get($validated_data, 'delivery_address')),
            'delivery_point' => $this->nullableString(Arr::get($delivery_point, 'description')),
            'delivery_point_id' => $this->nullableString($delivery_point_id),
            'postcode' => $this->nullableString(Arr::get($delivery_point, 'postcode')),
            'provider_data' => $delivery_point !== [] ? $delivery_point : null,
        ]);
    }

    private function resolveDeliveryMethodName(?string $code, string $locale): ?string
    {
        if ($code === null) {
            return null;
        }

        $translation_key = match ($code) {
            'nova_poshta' => 'catalog/pages/checkout.delivery_methods.nova_poshta',
            'nova_poshta_courier' => 'catalog/pages/checkout.delivery_methods.nova_poshta_courier',
            'nova_poshta_poshtomat' => 'catalog/pages/checkout.delivery_methods.nova_poshta_poshtomat',
            'ukr_poshta' => 'catalog/pages/checkout.delivery_methods.ukr_poshta',
            'pickup_store' => 'pickup::storefront/checkout.delivery_method',
            default => null,
        };

        return $this->resolveLocalizedName($translation_key, $code, $locale);
    }

    private function resolvePaymentMethodName(?string $code, string $locale): ?string
    {
        if ($code === null) {
            return null;
        }

        $configured_name = match ($code) {
            BankTransferConfig::PAYMENT_METHOD => $this->bank_transfer_config->getPaymentName($locale),
            default => $code === $this->wayforpay_config->getPaymentMethod()
                ? $this->wayforpay_config->getPaymentName($locale)
                : '',
        };

        if ($configured_name !== '') {
            return $configured_name;
        }

        $translation_key = match ($code) {
            'payment_upon_delivery' => 'paymentupondelivery::storefront/checkout.payment_methods.payment_upon_delivery',
            'cash_on_delivery' => 'catalog/pages/checkout.payment_methods.cash_on_delivery',
            BankTransferConfig::PAYMENT_METHOD => 'banktransfer::storefront/checkout.payment_methods.bank_transfer',
            default => $code === $this->wayforpay_config->getPaymentMethod()
                ? $this->wayforpay_config->getTranslationKey()
                : null,
        };

        return $this->resolveLocalizedName($translation_key, $code, $locale);
    }

    private function resolveLocalizedName(?string $translation_key, string $fallback, string $locale): string
    {
        if ($translation_key === null) {
            return $fallback;
        }

        $localized_name = Str::trim((string) Lang::get($translation_key, [], $locale));

        return $localized_name !== $translation_key ? $localized_name : $fallback;
    }

    private function resolveTotalType(string $code): ?TotalTypesEnum
    {
        return match ($code) {
            'items_subtotal', 'sub_total' => TotalTypesEnum::Subtotal,
            'shipping' => TotalTypesEnum::Shipping,
            'discount' => TotalTypesEnum::Discount,
            'promo_code' => TotalTypesEnum::PromoCode,
            'total', 'grand_total' => TotalTypesEnum::Total,
            default => null,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = Str::trim((string)$value);

        return $value !== '' ? $value : null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) && (int)$value > 0 ? (int)$value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }
}
