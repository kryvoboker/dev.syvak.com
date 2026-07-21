<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Services;

use App\Enums\Order\OrderDataKeyEnum;
use Modules\PaymentUponDelivery\Support\PaymentUponDeliveryConfig;

final class PaymentUponDeliveryPaymentModule
{
    /**
     * @param array<string, mixed> $order_payload
     * @return array{is_success: bool, status: string, provider_code: string, payload: array<string, mixed>}
     */
    public function process(array $order_payload): array
    {
        $order_payload[OrderDataKeyEnum::PaymentMethod->value] = PaymentUponDeliveryConfig::PAYMENT_METHOD;

        return [
            'is_success' => true,
            'status' => 'pending',
            'provider_code' => PaymentUponDeliveryConfig::PAYMENT_METHOD,
            'payload' => $order_payload,
        ];
    }
}
