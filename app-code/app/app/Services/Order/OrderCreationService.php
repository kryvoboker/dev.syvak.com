<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Services\Cart\CartService;
use App\Services\Order\Payment\CashOnDeliveryPaymentModule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Support\WayForPayConfig;

readonly class OrderCreationService
{
    public function __construct(
        private CartService $cart_service,
        private CashOnDeliveryPaymentModule $cash_on_delivery_payment_module,
        private PaymentUponDeliveryPaymentModule $payment_upon_delivery_payment_module,
        private BankTransferPaymentModule $bank_transfer_payment_module,
        private WayForPayPaymentModule $wayforpay_payment_module,
        private WayForPayConfig $wayforpay_config,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function validateFastOrderData(array $validated_data, string $locale): array
    {
        $cart_mode = (string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::FastOrder->value);
        $cart_data = $this->cart_service->getSnapshot($locale, $cart_mode);

        if ((bool) Arr::get($cart_data, 'is_empty', true) === true) {
            return [
                'success' => false,
                'errors' => [
                    'cart' => [__('catalog/default.cart.messages.cart_is_empty')],
                ],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'cart' => $cart_data,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function createFastOrder(array $validated_data, string $locale): array
    {
        $validation_result = $this->validateFastOrderData($validated_data, $locale);

        if ((bool) Arr::get($validation_result, 'success', false) === false) {
            return [
                'success' => false,
                'order_number' => null,
                'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
                'errors' => (array) Arr::get($validation_result, 'errors', []),
            ];
        }

        $order_number = $this->generateOrderNumber();
        $payment_method = (string) Arr::get($validated_data, 'payment_method', 'cash_on_delivery');

        // TODO: replace temporary payload with real order entity persistence.
        $order_payload = [
            'order_number' => $order_number,
            'customer' => [
                'first_name' => (string) Arr::get($validated_data, 'first_name', ''),
                'last_name' => (string) Arr::get($validated_data, 'last_name', ''),
                'phone' => clear_telephone((string) Arr::get($validated_data, 'phone', '')),
            ],
            'cart' => Arr::get($validation_result, 'cart', []),
            'locale' => $locale,
            'payment_method' => $payment_method,
        ];

        $payment_result = match ($payment_method) {
            PaymentUponDeliveryConfig::PAYMENT_METHOD => $this->payment_upon_delivery_payment_module->process($order_payload),
            BankTransferConfig::PAYMENT_METHOD => $this->bank_transfer_payment_module->process($order_payload),
            default => $this->cash_on_delivery_payment_module->process($order_payload),
        };

        $is_success = (bool) Arr::get($payment_result, 'is_success', false);

        if ($is_success === true) {
            $this->cart_service->clearCart((string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::FastOrder->value));

            return [
                'success' => true,
                'order_number' => $order_number,
                'redirect_url' => localized_route('localized.catalog.thank-you.index', ['locale' => $locale]),
                'status' => 'success',
                'errors' => [],
            ];
        }

        // Keep cart untouched for failed payment flow.
        return [
            'success' => false,
            'order_number' => $order_number,
            'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
            'status' => 'failed',
            'errors' => [
                'payment' => [__('catalog/default.cart.messages.payment_failed')],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function validateOrderData(array $validated_data, string $locale): array
    {
        return $this->validateFastOrderData($validated_data, $locale);
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    public function createOrder(array $validated_data, string $locale): array
    {
        return $this->createFastOrder($validated_data, $locale);
    }

    /**
     * @param array<string, mixed> $validated_data
     * @return array<string, mixed>
     */
    public function validateSimpleOrderData(array $validated_data, string $locale): array
    {
        unset($validated_data);

        $cart_data = $this->cart_service->getSnapshot($locale, CartModeEnum::Regular->value);

        if ((bool) Arr::get($cart_data, 'is_empty', true) === true) {
            return [
                'success' => false,
                'errors' => [
                    'cart' => [__('catalog/default.cart.messages.cart_is_empty')],
                ],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'cart' => $cart_data,
        ];
    }

    /**
     * @param array<string, mixed> $validated_data
     * @return array<string, mixed>
     */
    public function createSimpleOrder(array $validated_data, string $locale): array
    {
        $validation_result = $this->validateSimpleOrderData($validated_data, $locale);

        if ((bool) Arr::get($validation_result, 'success', false) === false) {
            return [
                'success' => false,
                'status' => 'failed',
                'errors' => (array) Arr::get($validation_result, 'errors', []),
            ];
        }

        $order_number = $this->generateOrderNumber('ORD');
        $payment_method = (string) Arr::get($validated_data, 'payment_method', '');
        $order_payload = [
            'order_number' => $order_number,
            'customer' => [
                'first_name' => (string) Arr::get($validated_data, 'first_name', ''),
                'last_name' => (string) Arr::get($validated_data, 'last_name', ''),
                'email' => (string) Arr::get($validated_data, 'email', ''),
                'phone' => clear_telephone((string) Arr::get($validated_data, 'phone', '')),
            ],
            'delivery' => [
                'method' => (string) Arr::get($validated_data, 'delivery_method', ''),
                'address' => (string) Arr::get($validated_data, 'delivery_address', ''),
            ],
            'cart' => Arr::get($validation_result, 'cart', []),
            'locale' => $locale,
            'payment_method' => $payment_method,
            'return_url' => route($this->wayforpay_config->getReturnRouteName(), ['locale' => $locale]),
            'service_url' => route($this->wayforpay_config->getCallbackRouteName(), ['locale' => $locale]),
        ];

        if ($payment_method === $this->wayforpay_config->getPaymentMethod()) {
            $payment_result = $this->wayforpay_payment_module->prepare($order_payload);

            if (($payment_result['success'] ?? false) !== true) {
                return [
                    'success' => false,
                    'order_number' => $order_number,
                    'status' => 'failed',
                    'errors' => (array) Arr::get($payment_result, 'errors', []),
                ];
            }

            return [
                'success' => true,
                'order_number' => $order_number,
                'status' => 'pending',
                'payment' => $payment_result,
                'errors' => [],
            ];
        }

        $payment_result = match ($payment_method) {
            PaymentUponDeliveryConfig::PAYMENT_METHOD => $this->payment_upon_delivery_payment_module->process($order_payload),
            BankTransferConfig::PAYMENT_METHOD => $this->bank_transfer_payment_module->process($order_payload),
            default => $this->cash_on_delivery_payment_module->process($order_payload),
        };

        if ((bool) Arr::get($payment_result, 'is_success', false) === true) {
            $this->cart_service->clearCart(CartModeEnum::Regular->value);

            return [
                'success' => true,
                'order_number' => $order_number,
                'redirect_url' => localized_route('localized.catalog.thank-you.index', ['locale' => $locale]),
                'status' => 'success',
                'errors' => [],
            ];
        }

        return [
            'success' => false,
            'order_number' => $order_number,
            'status' => 'failed',
            'errors' => [
                'payment' => [__('catalog/default.cart.messages.payment_failed')],
            ],
        ];
    }

    private function generateOrderNumber(string $prefix = 'TMP'): string
    {
        return $prefix . '-' . now(config('app.timezone'))->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}
