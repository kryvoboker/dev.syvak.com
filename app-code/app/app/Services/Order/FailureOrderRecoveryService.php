<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Cart\CartModeEnum;
use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\BankTransfer\Services\BankTransferPaymentModule;
use Modules\BankTransfer\Services\Storefront\BankTransferModuleDataService;
use Modules\BankTransfer\Support\BankTransferConfig;
use Modules\PaymentUponDelivery\Services\PaymentUponDeliveryPaymentModule;
use Modules\PaymentUponDelivery\Services\Storefront\PaymentUponDeliveryModuleDataService;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;
use Modules\WayForPay\Services\Storefront\WayForPayModuleDataService;
use Modules\WayForPay\Services\WayForPayPaymentModule;
use Modules\WayForPay\Support\WayForPayConfig;
use Throwable;

final readonly class FailureOrderRecoveryService
{
    private const string SESSION_KEY = 'order.failure_recovery';

    public function __construct(
        private Request $request,
        private CartService $cart_service,
        private OrderLifecycleService $order_lifecycle_service,
        private PaymentUponDeliveryPaymentModule $payment_upon_delivery_payment_module,
        private PaymentUponDeliveryModuleDataService $payment_upon_delivery_data_service,
        private BankTransferPaymentModule $bank_transfer_payment_module,
        private BankTransferModuleDataService $bank_transfer_data_service,
        private WayForPayPaymentModule $wayforpay_payment_module,
        private WayForPayModuleDataService $wayforpay_data_service,
        private WayForPayConfig $wayforpay_config,
    ) {
    }

    public function remember(Orders $order): void
    {
        if (! $this->request->hasSession()) {
            return;
        }

        $this->request->session()->put(self::SESSION_KEY, [
            'order_number' => (string) $order->order_number,
            'order_type' => $order->order_type->value,
            'retry_count' => (int) $this->request->session()->get(self::SESSION_KEY . '.retry_count', 0),
        ]);
    }

    public function rememberByOrderNumber(string $order_number): void
    {
        $order = Orders::query()->where('order_number', $order_number)->first();

        if ($order instanceof Orders) {
            $this->remember($order);
        }
    }

    /** @return array<string, mixed> */
    public function getState(): array
    {
        $state = $this->request->hasSession() ? $this->request->session()->get(self::SESSION_KEY, []) : [];

        return is_array($state) ? $state : [];
    }

    public function resolveOrder(): ?Orders
    {
        $order_number = (string) Arr::get($this->getState(), 'order_number', '');

        if ($order_number === '') {
            return null;
        }

        return Orders::query()
            ->with(['customer', 'shipping', 'products', 'totals', 'status', 'payments.paymentStatus'])
            ->where('order_number', $order_number)
            ->first();
    }

    public function getRetryPaymentMethod(): string
    {
        return (string) $this->resolveOrder()?->payments->sortByDesc('id')->first()?->code;
    }

    /** @return array<string, mixed> */
    public function retry(string $payment_method, string $locale): array
    {
        $order = $this->resolveOrder();

        if (! $order instanceof Orders || ! $this->isAvailablePaymentMethod($payment_method, $locale)) {
            return [
                'success' => false,
                'errors' => ['payment' => [__('storefront/failure.errors.payment_unavailable', [], $locale)]],
            ];
        }

        $payment = $order->payments()->create([
            'method' => $payment_method,
            'code' => $payment_method,
            'payment_status_id' => $this->order_lifecycle_service->getDefaultPaymentStatus()->getKey(),
            'amount' => (float) $order->total,
        ]);
        $payload = $this->buildOrderPayload($order, $payment_method, $locale);

        try {
            if ($payment_method === $this->wayforpay_config->getPaymentMethod()) {
                $payment_result = $this->wayforpay_payment_module->prepare($payload);

                if ($payment_result['success'] !== true) {
                    $this->markFailed($payment, (array) Arr::get($payment_result, 'errors', []));
                    $this->incrementRetryCount();

                    return ['success' => false, 'errors' => (array) Arr::get($payment_result, 'errors', [])];
                }

                $this->incrementRetryCount();

                return [
                    'success' => true,
                    'status' => 'pending',
                    'payment_id' => $payment->getKey(),
                    'payment' => $payment_result,
                    'errors' => [],
                ];
            }

            $payment_result = match ($payment_method) {
                PaymentUponDeliveryConfig::PAYMENT_METHOD => $this->payment_upon_delivery_payment_module->process($payload),
                BankTransferConfig::PAYMENT_METHOD => $this->bank_transfer_payment_module->process($payload),
                default => [
                    'is_success' => true,
                    'status' => 'pending',
                    'errors' => [],
                ],
            };

            if (! (bool) Arr::get($payment_result, 'is_success', false)) {
                $this->markFailed($payment, (array) Arr::get($payment_result, 'errors', []));
                $this->incrementRetryCount();

                return ['success' => false, 'errors' => (array) Arr::get($payment_result, 'errors', [])];
            }

            $this->order_lifecycle_service->transitionPayment($payment, OrderLifecycleService::PAYMENT_STATUS_PAID, [
                'source' => 'failure_page_retry',
            ]);
            $this->order_lifecycle_service->transitionOrderForPayment($order, OrderLifecycleService::PAYMENT_STATUS_PAID, [
                'payment_id' => $payment->getKey(),
            ]);
            $this->cart_service->clearCart((string) Arr::get($this->getState(), 'order_type', CartModeEnum::Regular->value));
            $this->forget();

            return [
                'success' => true,
                'status' => 'success',
                'redirect_url' => localized_route('localized.catalog.thank-you.index', [
                    'locale' => $locale,
                    'order_number' => $order->order_number,
                ]),
                'errors' => [],
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[FailureOrderRecoveryService] retry failed', [
                'order_number' => $order->order_number,
                'payment_method' => $payment_method,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
            $this->markFailed($payment, ['payment' => [$throwable->getMessage()]]);
            $this->incrementRetryCount();

            return [
                'success' => false,
                'errors' => ['payment' => [__('storefront/failure.errors.retry_failed', [], $locale)]],
            ];
        }
    }

    public function forget(): void
    {
        if ($this->request->hasSession()) {
            $this->request->session()->forget(self::SESSION_KEY);
        }
    }

    private function incrementRetryCount(): void
    {
        if ($this->request->hasSession()) {
            $this->request->session()->increment(self::SESSION_KEY . '.retry_count');
        }
    }

    private function isAvailablePaymentMethod(string $payment_method, string $locale): bool
    {
        if ($payment_method === $this->wayforpay_config->getPaymentMethod()) {
            return (bool) Arr::get($this->wayforpay_data_service->getCheckoutData($locale), 'is_available', false);
        }

        $methods = [
            $this->payment_upon_delivery_data_service->getCheckoutData(),
            $this->bank_transfer_data_service->getCheckoutData($locale),
        ];

        if ($payment_method === 'cash_on_delivery') {
            return true;
        }

        return collect($methods)->contains(
            fn (array $method): bool =>
            (bool) Arr::get($method, 'is_available', false)
            && (string) Arr::get($method, 'payment_method') === $payment_method,
        );
    }

    /** @return array<string, mixed> */
    private function buildOrderPayload(Orders $order, string $payment_method, string $locale): array
    {
        $items = $order->products->map(fn (mixed $product): array => [
            'name' => (string) $product->name,
            'unit_price' => (float) $product->unit_price,
            'quantity' => (int) $product->quantity,
            'line_total' => (float) $product->line_total,
        ])->values()->all();
        $totals = $order->totals->map(fn (mixed $total): array => [
            'code' => (string) data_get($total, 'total_type.value', $total->total_type),
            'label' => (string) $total->name,
            'amount' => (float) $total->value,
        ])->values()->all();

        return [
            'order_number' => (string) $order->order_number,
            'customer' => [
                'first_name' => (string) $order->customer?->first_name,
                'last_name' => (string) $order->customer?->last_name,
                'email' => (string) $order->customer?->email,
                'phone' => (string) $order->customer?->telephone,
            ],
            'delivery' => [
                'method' => (string) $order->shipping?->code,
                'address' => (string) $order->shipping?->address,
            ],
            'cart' => [
                'items' => $items,
                'totals' => [
                    'lines' => $totals,
                    'grand_total' => (float) $order->total,
                    'currency_code' => (string) $order->currency_code,
                ],
            ],
            'locale' => $locale,
            'payment_method' => $payment_method,
            'return_url' => route($this->wayforpay_config->getReturnRouteName(), ['locale' => $locale]),
            'service_url' => route($this->wayforpay_config->getCallbackRouteName(), ['locale' => $locale]),
        ];
    }

    /** @param array<string, mixed> $errors */
    private function markFailed(OrderPayments $payment, array $errors): void
    {
        $this->order_lifecycle_service->transitionPayment(
            $payment,
            OrderLifecycleService::PAYMENT_STATUS_FAILED,
            ['source' => 'failure_page_retry'],
            Arr::flatten($errors)[0] ?? null,
        );
    }
}
