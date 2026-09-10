<?php

declare(strict_types=1);

namespace App\Enums\Order;

enum OrderNotificationDeliveryStatusEnum: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
