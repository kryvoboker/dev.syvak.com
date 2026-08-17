<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Cart\CartModeEnum;
use App\Enums\Cart\CartRequestKeyEnum;
use App\Enums\Order\OrderDataKeyEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Services\Cart\CartService;
use App\Services\Marketing\PromoCodeService;
use App\Services\Order\Payment\CashOnDeliveryPaymentModule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\Pickup\Services\Storefront\PickupCheckoutDataService;
use Modules\Pickup\Support\PickupConfig;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;

readonly class OrderCreationService
{
    public function __construct(
        private CartService $cart_service,
        private CashOnDeliveryPaymentModule $cash_on_delivery_payment_module,
        private PaymentUponDeliveryPaymentModule $payment_upon_delivery_payment_module,
        private BankTransferPaymentModule $bank_transfer_payment_module,
        private WayForPayPaymentModule $wayforpay_payment_module,
        private WayForPayConfig $wayforpay_config,
        private OrderAggregatePersistenceService $order_aggregate_persistence_service,
        private OrderLifecycleService $order_lifecycle_service,
        private PickupCheckoutDataService $pickup_checkout_data_service,
        private ?PromoCodeService $promo_code_service = null,
        private ?FailureOrderRecoveryService $failure_order_recovery_service = null,
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
                    'cart' => [__('storefront/default.cart.messages.cart_is_empty')],
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

        $payment_method = (string) Arr::get($validated_data, OrderDataKeyEnum::PaymentMethod->value, PaymentMethodEnum::CashOnDelivery->value);

        try {
            $persisted_order = $this->order_aggregate_persistence_service->createSimpleOrder(
                $validated_data,
                (array) Arr::get($validation_result, 'cart', []),
                $locale,
                $this->resolveRequestContext(),
            );
            $order = $persisted_order['order'];
            $payment = $persisted_order['payment'];
            $order_number = (string) $order->order_number;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[OrderCreationService.createFastOrder] order persistence failed', [
                'flow' => CartModeEnum::FastOrder->value,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'order_number' => null,
                'status' => 'failed',
                'errors' => [
                    'order' => [__('storefront/default.cart.messages.payment_failed')],
                ],
            ];
        }

        $order_payload = [
            'order_number' => $order_number,
            'customer' => [
                'first_name' => (string) Arr::get($validated_data, 'first_name', ''),
                'last_name' => (string) Arr::get($validated_data, 'last_name', ''),
                'phone' => clear_telephone((string) Arr::get($validated_data, 'phone', '')),
            ],
            'cart' => Arr::get($validation_result, 'cart', []),
            'locale' => $locale,
            OrderDataKeyEnum::PaymentMethod->value => $payment_method,
        ];

        $payment_result = match ($payment_method) {
            PaymentUponDeliveryConfig::PAYMENT_METHOD => $this->payment_upon_delivery_payment_module->process($order_payload),
            BankTransferConfig::PAYMENT_METHOD => $this->bank_transfer_payment_module->process($order_payload),
            default => $this->cash_on_delivery_payment_module->process($order_payload),
        };

        $is_success = (bool) Arr::get($payment_result, 'is_success', false);

        if ($is_success === true) {
            $this->cart_service->clearCart((string) Arr::get($validated_data, CartRequestKeyEnum::CartMode->value, CartModeEnum::FastOrder->value));
            $this->consumePromoCode((array) Arr::get($validation_result, 'cart', []), $order);

            return [
                'success' => true,
                'order_number' => $order_number,
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => $order_number,
                ]),
                'status' => 'success',
                'errors' => [],
            ];
        }

        $this->markPaymentFailed($payment, (array) Arr::get($payment_result, 'errors', []));
        $this->failure_order_recovery_service?->remember($order);

        // Keep cart untouched for failed payment flow.
        return [
            'success' => false,
            'order_number' => $order_number,
            'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
            'status' => 'failed',
            'errors' => [
                'payment' => [__('storefront/default.cart.messages.payment_failed')],
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
                    'cart' => [__('storefront/default.cart.messages.cart_is_empty')],
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

        $payment_method = (string) Arr::get($validated_data, OrderDataKeyEnum::PaymentMethod->value, '');

        if (Arr::get($validated_data, OrderDataKeyEnum::DeliveryMethod->value) === PickupConfig::DELIVERY_METHOD) {
            $pickup_data = $this->pickup_checkout_data_service->getCheckoutData($locale);
            $validated_data['city'] = [];
            $validated_data[OrderDataKeyEnum::DeliveryPoint->value] = [];
            $validated_data[OrderDataKeyEnum::DeliveryAddress->value] = (string) Arr::get($pickup_data, 'store_address', '');
        }

        try {
            $persisted_order = $this->order_aggregate_persistence_service->createSimpleOrder(
                $validated_data,
                (array) Arr::get($validation_result, 'cart', []),
                $locale,
                $this->resolveRequestContext(),
            );
            $order = $persisted_order['order'];
            $payment = $persisted_order['payment'];
            $order_number = (string) $order->order_number;
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[OrderCreationService.createSimpleOrder] order persistence failed', [
                'flow' => CartModeEnum::Regular->value,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return [
                'success' => false,
                'order_number' => null,
                'status' => 'failed',
                'errors' => [
                    'order' => [__('storefront/default.cart.messages.payment_failed')],
                ],
            ];
        }

        $order_payload = [
            'order_number' => $order_number,
            'customer' => [
                'first_name' => (string) Arr::get($validated_data, 'first_name', ''),
                'last_name' => (string) Arr::get($validated_data, 'last_name', ''),
                'email' => (string) Arr::get($validated_data, 'email', ''),
                'phone' => clear_telephone((string) Arr::get($validated_data, 'phone', '')),
            ],
            'delivery' => [
                'method' => (string) Arr::get($validated_data, OrderDataKeyEnum::DeliveryMethod->value, ''),
                'address' => (string) Arr::get($validated_data, OrderDataKeyEnum::DeliveryAddress->value, ''),
            ],
            'cart' => Arr::get($validation_result, 'cart', []),
            'locale' => $locale,
                OrderDataKeyEnum::PaymentMethod->value => $payment_method,
            'return_url' => route($this->wayforpay_config->getReturnRouteName(), ['locale' => $locale]),
            'service_url' => route($this->wayforpay_config->getCallbackRouteName(), ['locale' => $locale]),
        ];

        if ($payment_method === $this->wayforpay_config->getPaymentMethod()) {
            $payment_result = $this->wayforpay_payment_module->prepare($order_payload);

            if ($payment_result['success'] !== true) {
                $this->markPaymentFailed($payment, (array) Arr::get($payment_result, 'errors', []));
                $this->failure_order_recovery_service?->remember($order);

                return [
                    'success' => false,
                    'order_number' => $order_number,
                    'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
                    'status' => 'failed',
                    'errors' => (array) Arr::get($payment_result, 'errors', []),
                ];
            }

            return [
                'success' => true,
                'order_number' => $order_number,
                'status' => 'pending',
                'payment_id' => $payment->getKey(),
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
            $this->consumePromoCode((array) Arr::get($validation_result, 'cart', []), $order);

            return [
                'success' => true,
                'order_number' => $order_number,
                'payment_id' => $payment->getKey(),
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => $order_number,
                ]),
                'status' => 'success',
                'errors' => [],
            ];
        }

        $this->markPaymentFailed($payment, (array) Arr::get($payment_result, 'errors', []));
        $this->failure_order_recovery_service?->remember($order);

        return [
            'success' => false,
            'order_number' => $order_number,
            'redirect_url' => localized_route('localized.catalog.failure-order.index', ['locale' => $locale]),
            'status' => 'failed',
            'errors' => [
                'payment' => [__('storefront/default.cart.messages.payment_failed')],
            ],
        ];
    }

    /**
     * @return array{ip: string, forwarded_ip: ?string, user_agent: ?string, accept_language: ?string}
     */
    private function resolveRequestContext(): array
    {
        $request = request();

        return [
            'ip' => $request->ip() ?: '0.0.0.0',
            'forwarded_ip' => $request->header('X-Forwarded-For'),
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
        ];
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function markPaymentFailed(OrderPayments $payment, array $errors): void
    {
        try {
            $this->order_lifecycle_service->transitionPayment(
                $payment,
                OrderLifecycleService::PAYMENT_STATUS_FAILED,
                ['source' => 'payment_preparation'],
                Arr::flatten($errors)[0] ?? null,
            );
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[OrderCreationService] failed payment status update failed', [
                'payment_id' => $payment->getKey(),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Consume a promo code only after the payment module confirms the order.
     *
     * @param array<string, mixed> $cart_data
     */
    private function consumePromoCode(array $cart_data, Orders $order): void
    {
        $promo_code_id = Arr::get($cart_data, 'totals.promo_code.promo_code_id');

        if (! is_numeric($promo_code_id)) {
            return;
        }

        if ($this->promo_code_service === null) {
            return;
        }

        $promo_code = $this->promo_code_service->resolve((string) Arr::get($cart_data, 'totals.promo_code.code', ''));

        if ($promo_code === null || (int) $promo_code->getKey() !== (int) $promo_code_id) {
            return;
        }

        $app_settings = get_app_settings();

        try {
            $this->promo_code_service->consume(
                $promo_code,
                $order,
                auth()->id(),
                is_numeric($app_settings?->user_group_id) ? (int) $app_settings->user_group_id : null,
            );
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[OrderCreationService] promo code consumption failed', [
                'promo_code_id' => $promo_code->getKey(),
                'order_id' => $order->getKey(),
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
