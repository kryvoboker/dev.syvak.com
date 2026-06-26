<?php

declare(strict_types=1);

namespace App\Services\Order\Payment;

class WayForPayPaymentModule
{
    /**
     * TODO:
     * - initialize WayForPay payment transaction
     * - handle callback/webhook result
     * - map provider status to local order status
     */
    public function process(array $order_payload): array
    {
        return [
            'is_success' => false,
            'status' => 'failed',
            'provider_code' => 'wayforpay_not_implemented',
            'payload' => $order_payload,
        ];
    }
}
