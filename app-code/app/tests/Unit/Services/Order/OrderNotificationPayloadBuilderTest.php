<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Order;

use App\Services\Order\OrderNotificationPayloadBuilder;
use PHPUnit\Framework\TestCase;

final class OrderNotificationPayloadBuilderTest extends TestCase
{
    public function test_it_formats_the_common_notification_content(): void
    {
        $text = (new OrderNotificationPayloadBuilder())->formatText([
            'order' => [
                'number' => '01test',
                'status' => 'failed',
                'total' => 25,
                'currency' => 'UAH',
            ],
            'customer' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
            'payment' => ['status' => 'failed'],
            'promo_code' => ['code' => 'TEST', 'discount_amount' => 5],
            'products' => [['name' => 'T-shirt', 'quantity' => 2, 'line_total' => 25]],
        ]);

        self::assertStringContainsString('Order: 01test', $text);
        self::assertStringContainsString('Promo code: TEST', $text);
        self::assertStringContainsString('T-shirt x2: 25', $text);
    }
}
