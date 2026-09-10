<?php

declare(strict_types=1);

namespace App\Enums\Order;

enum OrderNotificationOutcomeEnum: string
{
    case Success = 'success';
    case Failure = 'failure';
}
