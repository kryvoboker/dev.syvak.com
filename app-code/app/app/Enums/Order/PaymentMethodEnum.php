<?php

declare(strict_types=1);

namespace App\Enums\Order;

use App\Enums\EnumValuesTrait;

enum PaymentMethodEnum: string
{
    use EnumValuesTrait;

    case CashOnDelivery = 'cash_on_delivery';
    case PaymentUponDelivery = 'payment_upon_delivery';
    case BankTransfer = 'bank_transfer';
    case WayForPay = 'wayforpay';
}
