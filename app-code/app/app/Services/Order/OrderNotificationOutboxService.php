<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Order\OrderNotificationEventStatusEnum;
use App\Enums\Order\OrderNotificationOutcomeEnum;
use App\Jobs\PublishOrderNotificationEventJob;
use App\Models\Orders\OrderNotificationEvent;
use App\Models\Orders\Orders;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class OrderNotificationOutboxService
{
    public function __construct(private OrderNotificationPayloadBuilder $payload_builder)
    {
    }

    /**
     * @param Orders                       $order
     * @param OrderNotificationOutcomeEnum $outcome
     *
     * @return OrderNotificationEvent|null
     */
    public function record(Orders $order, OrderNotificationOutcomeEnum $outcome): ?OrderNotificationEvent
    {
        try {
            $event = $order->notificationEvents()->firstOrCreate(
                ['outcome' => $outcome],
                [
                    'event_id' => Str::lower((string) Str::ulid()),
                    'schema_version' => integer_value(config('order-notifications.schema_version', 1)),
                    'payload' => $this->payload_builder->build($order, $outcome),
                    'status' => OrderNotificationEventStatusEnum::Pending,
                ],
            );

            PublishOrderNotificationEventJob::dispatch(integer_value($event->getKey()));

            return $event;
        } catch (Throwable $throwable) {
            Log::channel('stack')->critical('[OrderNotificationOutboxService] event creation failed', [
                'order_id' => $order->getKey(),
                'outcome' => $outcome->value,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }
    }
}
