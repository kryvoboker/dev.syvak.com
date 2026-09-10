<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Order\OrderNotificationEventStatusEnum;
use App\Models\Orders\OrderNotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Throwable;

final class PublishOrderNotificationEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(private readonly int $event_id)
    {
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $event = OrderNotificationEvent::query()->find($this->event_id);

        if (! $event instanceof OrderNotificationEvent || $event->status === OrderNotificationEventStatusEnum::Published) {
            return;
        }

        try {
            Kafka::publish(string_value(config('kafka.brokers')))
                ->onTopic(string_value(config('order-notifications.topic')))
                ->withMessage(new Message(
                    body: [
                        'event_id' => $event->event_id,
                        'schema_version' => $event->schema_version,
                        'order_id' => integer_value($event->order_id),
                        'outcome' => $event->outcome->value,
                        'payload' => $event->payload,
                    ],
                    key: (string) $event->order_id,
                ))
                ->send();

            $event->forceFill([
                'status' => OrderNotificationEventStatusEnum::Published,
                'published_at' => now(),
                'attempts' => $event->attempts + 1,
                'last_error' => null,
            ])->saveQuietly();
        } catch (Throwable $throwable) {
            $event->forceFill([
                'status' => OrderNotificationEventStatusEnum::Failed,
                'attempts' => $event->attempts + 1,
                'last_error' => $throwable->getMessage(),
                'failed_at' => now(),
            ])->saveQuietly();

            Log::channel('stack')->error('[PublishOrderNotificationEventJob] Kafka publish failed', [
                'event_id' => $event->event_id,
                'order_id' => $event->order_id,
                'attempt' => $event->attempts,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
