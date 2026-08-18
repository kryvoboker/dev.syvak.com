<?php

declare(strict_types=1);

namespace Modules\WayForPay\Services;

use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Services\Order\OrderLifecycleService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class WayForPayOrderPaymentService
{
    public function __construct(
        private readonly OrderLifecycleService $order_lifecycle_service,
    ) {
    }

    /**
     * @param array<string, mixed> $provider_data
     */
    public function applyProviderResponse(array $provider_data): Orders
    {
        return DB::transaction(
            fn (): Orders => $this->applyProviderResponseInTransaction($provider_data),
        );
    }

    /**
     * @param array<string, mixed> $provider_data
     */
    private function applyProviderResponseInTransaction(array $provider_data): Orders
    {
        $order_reference = trim((string) Arr::get($provider_data, 'orderReference', ''));

        if ($order_reference === '') {
            throw new RuntimeException('WayForPay response does not contain an order reference.');
        }

        $order = Orders::query()
            ->with(['status', 'payments.paymentStatus'])
            ->where('order_number', $order_reference)
            ->first();

        if (! $order instanceof Orders) {
            Log::channel('stack')->error('[WayForPayOrderPaymentService] order reference was not found', [
                'order_reference' => $order_reference,
            ]);

            throw new RuntimeException('WayForPay order reference was not found.');
        }

        $payment = $this->resolvePayment($order, $provider_data);
        $payment_status = $this->resolvePaymentStatus((string) Arr::get($provider_data, 'transactionStatus', ''));
        $transaction_id = trim((string) Arr::get($provider_data, 'transactionId', ''));

        if ($transaction_id !== '') {
            $payment->transaction_id = $transaction_id;
            $payment->saveQuietly();
        }

        $this->order_lifecycle_service->transitionPayment(
            $payment,
            $payment_status,
            $this->filterProviderData($provider_data),
            $this->resolveFailureReason($provider_data),
        );
        $this->order_lifecycle_service->transitionOrderForPayment($order, $payment_status, [
            'payment_id' => $payment->getKey(),
            'provider_status' => Arr::get($provider_data, 'transactionStatus'),
            'provider_reason_code' => Arr::get($provider_data, 'reasonCode'),
        ]);

        $fresh_order = $order->fresh(['status', 'payments.paymentStatus']);

        if (! $fresh_order instanceof Orders) {
            throw new RuntimeException('WayForPay order could not be refreshed after payment update.');
        }

        return $fresh_order;
    }

    /**
     * @param array<string, mixed> $provider_data
     */
    private function resolvePayment(Orders $order, array $provider_data): OrderPayments
    {
        $transaction_id = trim((string) Arr::get($provider_data, 'transactionId', ''));

        if ($transaction_id !== '') {
            $payment = $order->payments->first(
                fn (OrderPayments $payment): bool => $payment->transaction_id === $transaction_id,
            );

            if ($payment instanceof OrderPayments) {
                return $payment;
            }
        }

        $payment = $order->payments
            ->sortByDesc('id')
            ->first();

        if ($payment instanceof OrderPayments) {
            return $payment;
        }

        throw new RuntimeException('WayForPay order does not have a payment attempt.');
    }

    private function resolvePaymentStatus(string $provider_status): string
    {
        return match (mb_strtolower($provider_status)) {
            'approved' => OrderLifecycleService::PAYMENT_STATUS_PAID,
            'pending', 'inprocessing', 'processing' => OrderLifecycleService::PAYMENT_STATUS_PENDING,
            'cancelled', 'canceled' => OrderLifecycleService::PAYMENT_STATUS_CANCELLED,
            'declined' => OrderLifecycleService::PAYMENT_STATUS_DECLINED,
            'expired' => OrderLifecycleService::PAYMENT_STATUS_EXPIRED,
            default => OrderLifecycleService::PAYMENT_STATUS_FAILED,
        };
    }

    /**
     * @param array<string, mixed> $provider_data
     */
    private function resolveFailureReason(array $provider_data): ?string
    {
        $reason = trim((string) Arr::get($provider_data, 'reason', ''));

        return $reason !== '' ? $reason : null;
    }

    /**
     * @param array<string, mixed> $provider_data
     * @return array<string, mixed>
     */
    private function filterProviderData(array $provider_data): array
    {
        return Arr::only($provider_data, [
            'orderReference',
            'transactionStatus',
            'transactionId',
            'reason',
            'reasonCode',
            'amount',
            'currency',
            'authCode',
        ]);
    }
}
