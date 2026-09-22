<?php

declare(strict_types=1);

namespace App\Services\Order;

interface OrderNotificationPublisher
{
    /** @param array<string, mixed> $envelope */
    public function publish(array $envelope): void;
}
