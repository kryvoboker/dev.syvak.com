<?php

declare(strict_types=1);

namespace App\Enums\Order;

enum OrderNotificationEventStatusEnum: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Failed = 'failed';
}
