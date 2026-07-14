<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Services;

use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;

final class PaymentUponDeliveryPaymentModule
{
    /**
     * @param array<string, mixed> $order_payload
     * @return array{is_success: bool, status: string, provider_code: string, payload: array<string, mixed>}
     */
    public function process(array $order_payload): array
    {
        $order_payload['payment_method'] = PaymentUponDeliveryConfig::PAYMENT_METHOD;

        return [
            'is_success' => true,
            'status' => 'pending',
            'provider_code' => 'payment_upon_delivery',
            'payload' => $order_payload,
        ];
    }
}
