<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\CartModeEnum;
use App\Enums\CartRequestKeyEnum;
use App\Services\Cart\CartService;
use App\Services\Order\Payment\CashOnDeliveryPaymentModule;
use App\Services\Order\Payment\WayForPayPaymentModule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

readonly class OrderCreationService
{
    public function __construct(
        private CartService $cart_service,
        private WayForPayPaymentModule $way_for_pay_payment_module,
        private CashOnDeliveryPaymentModule $cash_on_delivery_payment_module,
    ) {}

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function validateOrderData(array $validated_data, string $locale): array
    {
        $cart_mode = (string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value);
        $cart_data = $this->cart_service->getSnapshot($locale, $cart_mode);

        if ((bool) Arr::get($cart_data, 'is_empty', true) === true) {
            return [
                'success' => false,
                'errors'  => [
                    'cart' => [__('catalog/default.cart.messages.cart_is_empty')],
                ],
            ];
        }

        return [
            'success' => true,
            'errors'  => [],
            'cart'    => $cart_data,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function createOrder(array $validated_data, string $locale): array
    {
        $validation_result = $this->validateOrderData($validated_data, $locale);

        if ((bool) Arr::get($validation_result, 'success', false) === false) {
            return [
                'success'      => false,
                'order_number' => null,
                'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
                'errors'       => (array) Arr::get($validation_result, 'errors', []),
            ];
        }

        $order_number   = $this->generateOrderNumber();
        $payment_method = (string) Arr::get($validated_data, 'payment_method', 'cash_on_delivery');

        // TODO: replace temporary payload with real order entity persistence.
        $order_payload = [
            'order_number' => $order_number,
            'customer'     => [
                'first_name' => (string) Arr::get($validated_data, 'first_name', ''),
                'last_name'  => (string) Arr::get($validated_data, 'last_name', ''),
                'phone'      => clear_telephone((string) Arr::get($validated_data, 'phone', '')),
            ],
            'cart'   => Arr::get($validation_result, 'cart', []),
            'locale' => $locale,
        ];

        $payment_result = $payment_method === 'wayforpay'
            ? $this->way_for_pay_payment_module->process($order_payload)
            : $this->cash_on_delivery_payment_module->process($order_payload);

        $is_success = (bool) Arr::get($payment_result, 'is_success', false);

        if ($is_success === true) {
            $this->cart_service->clearCart((string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::Regular->value));

            return [
                'success'      => true,
                'order_number' => $order_number,
                'redirect_url' => localized_route('localized.catalog.thank-you.index', ['locale' => $locale]),
                'status'       => 'success',
                'errors'       => [],
            ];
        }

        // Keep cart untouched for failed payment flow.
        return [
            'success'      => false,
            'order_number' => $order_number,
            'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
            'status'       => 'failed',
            'errors'       => [
                'payment' => [__('catalog/default.cart.messages.payment_failed')],
            ],
        ];
    }

    private function generateOrderNumber(): string
    {
        return 'TMP-' . now(config('app.timezone'))->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}
