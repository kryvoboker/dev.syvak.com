<?php

declare(strict_types=1);

namespace App\Services\Order\Payment;

class CashOnDeliveryPaymentModule
{
    /**
     * TODO:
     * - add COD-specific validation and constraints
     * - map COD flow into unified order status machine
     */
    public function process(array $order_payload): array
    {
        return [
            'is_success'    => true,
            'status'        => 'pending',
            'provider_code' => 'cod_not_implemented',
            'payload'       => $order_payload,
        ];
    }
}
