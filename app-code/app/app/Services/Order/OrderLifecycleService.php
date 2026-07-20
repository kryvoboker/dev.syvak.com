<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\Orders\OrderPayments;
use App\Models\Orders\Orders;
use App\Models\Orders\OrderStatuses;
use App\Models\Payment\PaymentStatuses;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OrderLifecycleService
{
    public const string PAYMENT_STATUS_PENDING = 'pending';

    public const string PAYMENT_STATUS_PAID = 'paid';

    public const string PAYMENT_STATUS_FAILED = 'failed';

    public const string PAYMENT_STATUS_CANCELLED = 'cancelled';

    public const string PAYMENT_STATUS_DECLINED = 'declined';

    public const string PAYMENT_STATUS_EXPIRED = 'expired';

    /**
     * @return OrderStatuses
     */
    public function getDefaultOrderStatus(): OrderStatuses
    {
        $status = new OrderStatuses()->getDefaultActiveStatus();

        if ($status instanceof OrderStatuses) {
            return $status;
        }

        $this->logMissingDefaultStatus('order_statuses');

        throw new RuntimeException('No active default order status is configured.');
    }

    /**
     * @return PaymentStatuses
     */
    public function getDefaultPaymentStatus(): PaymentStatuses
    {
        $status = new PaymentStatuses()->getDefaultActiveStatus();

        if ($status instanceof PaymentStatuses) {
            return $status;
        }

        $this->logMissingDefaultStatus('payment_statuses');

        throw new RuntimeException('No active default payment status is configured.');
    }

    public function getPaymentStatusByCode(string $code): PaymentStatuses
    {
        $status = PaymentStatuses::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($status instanceof PaymentStatuses) {
            return $status;
        }

        Log::channel('stack')->error('[OrderLifecycleService] payment status is not configured', [
            'status_code' => $code,
        ]);

        throw new RuntimeException(sprintf('Payment status [%s] is not configured.', $code));
    }

    public function getOrderStatusByCode(string $code): OrderStatuses
    {
        $status = OrderStatuses::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($status instanceof OrderStatuses) {
            return $status;
        }

        Log::channel('stack')->error('[OrderLifecycleService] order status is not configured', [
            'status_code' => $code,
        ]);

        throw new RuntimeException(sprintf('Order status [%s] is not configured.', $code));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function transitionOrderForPayment(Orders $order, string $payment_status, array $context = []): bool
    {
        $status_codes = match ($payment_status) {
            self::PAYMENT_STATUS_PAID => ['processing', 'paid', 'completed'],
            self::PAYMENT_STATUS_CANCELLED,
            self::PAYMENT_STATUS_DECLINED,
            self::PAYMENT_STATUS_EXPIRED,
            self::PAYMENT_STATUS_FAILED => ['cancelled', 'failed'],
            default => [],
        };

        foreach ($status_codes as $status_code) {
            $status = OrderStatuses::query()
                ->where('code', $status_code)
                ->where('is_active', true)
                ->first();

            if (! $status instanceof OrderStatuses) {
                continue;
            }

            return $this->transitionOrder($order, $status, 'payment_status_changed', [
                ...$context,
                'payment_status' => $payment_status,
            ]);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function transitionOrder(
        Orders $order,
        OrderStatuses $new_status,
        string $event,
        array $context = [],
    ): bool {
        $old_status = $order->status;

        if ($old_status?->is($new_status) === true) {
            return false;
        }

        $order->status()->associate($new_status);
        $order->saveQuietly();

        $order->histories()->create([
            'user_id' => null,
            'old_order_status_id' => $old_status?->getKey(),
            'order_status_id' => $new_status->getKey(),
            'event' => $event,
            'json' => $this->filterContext($context),
        ]);

        Log::channel('daily')->info('[OrderLifecycleService] order status changed', [
            'order_number' => $order->order_number,
            'old_status' => $old_status?->code,
            'new_status' => $new_status->code,
            'event' => $event,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $provider_data
     */
    public function transitionPayment(
        OrderPayments $payment,
        string $status_code,
        array $provider_data = [],
        ?string $failure_reason = null,
    ): bool {
        $new_status = $this->getPaymentStatusByCode($status_code);
        $old_status = $payment->paymentStatus;

        if ($old_status?->code === self::PAYMENT_STATUS_PAID && $status_code !== self::PAYMENT_STATUS_PAID) {
            Log::channel('stack')->error('[OrderLifecycleService] paid payment transition rejected', [
                'payment_id' => $payment->getKey(),
                'requested_status' => $status_code,
            ]);

            return false;
        }

        if ($old_status?->is($new_status) === true) {
            return false;
        }

        $payment->paymentStatus()->associate($new_status);
        $payment->provider_data = $provider_data !== [] ? $provider_data : $payment->provider_data;
        $payment->failure_reason = $failure_reason;
        $payment->paid_at = $status_code === self::PAYMENT_STATUS_PAID ? now()->toDateTimeString() : null;
        $payment->failed_at = in_array($status_code, [
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_CANCELLED,
            self::PAYMENT_STATUS_DECLINED,
            self::PAYMENT_STATUS_EXPIRED,
        ], true) ? now()->toDateTimeString() : null;
        $payment->saveQuietly();

        Log::channel('daily')->info('[OrderLifecycleService] payment status changed', [
            'payment_id' => $payment->getKey(),
            'old_status' => $old_status?->code,
            'new_status' => $new_status->code,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function filterContext(array $context): array
    {
        return Arr::only($context, [
            'payment_id',
            'payment_status',
            'provider_status',
            'provider_reason_code',
        ]);
    }

    private function logMissingDefaultStatus(string $table): void
    {
        Log::channel('stack')->error('[OrderLifecycleService] active default status is missing', [
            'table' => $table,
        ]);
    }
}
