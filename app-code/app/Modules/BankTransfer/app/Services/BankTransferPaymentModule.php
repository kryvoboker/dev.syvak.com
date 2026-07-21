<?php

declare(strict_types=1);

namespace Modules\BankTransfer\Services;

use App\Enums\Order\OrderDataKeyEnum;
use Modules\BankTransfer\Support\BankTransferConfig;

final class BankTransferPaymentModule
{
    /**
     * @param array<string, mixed> $order_payload
     * @return array{is_success: bool, status: string, provider_code: string, payload: array<string, mixed>}
     */
    public function process(array $order_payload): array
    {
        $order_payload[OrderDataKeyEnum::PaymentMethod->value] = BankTransferConfig::PAYMENT_METHOD;

        return [
            'is_success' => true,
            'status' => 'pending',
            'provider_code' => BankTransferConfig::PAYMENT_METHOD,
            'payload' => $order_payload,
        ];
    }
}
