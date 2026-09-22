<?php

declare(strict_types=1);

namespace App\Enums\Order;

enum OrderNotificationChannelEnum: string
{
    case Telegram = 'telegram';
    case SalesDrive = 'salesdrive';
    case Email = 'email';
}
