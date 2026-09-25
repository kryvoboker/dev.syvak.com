<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Order\OrderNotificationEventStatusEnum;
use App\Models\Orders\OrderNotificationEvent;
use App\Services\Order\OrderNotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessOrderNotificationEventJob implements ShouldQueue
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
     * @throws Throwable
     */
    public function handle(OrderNotificationDeliveryService $delivery_service): void
    {
        $event = OrderNotificationEvent::query()->find($this->event_id);

        if (! $event instanceof OrderNotificationEvent || $event->status === OrderNotificationEventStatusEnum::Published) {
            return;
        }

        try {
            $delivery_service->process([
                'event_id' => $event->event_id,
                'schema_version' => $event->schema_version,
                'order_id' => integer_value($event->order_id),
                'outcome' => $event->outcome->value,
                'payload' => $event->payload,
            ]);

            $event->forceFill([
                'status' => OrderNotificationEventStatusEnum::Published,
                'published_at' => now(),
                'attempts' => $event->attempts + 1,
                'last_error' => null,
                'failed_at' => null,
            ])->saveQuietly();
        } catch (Throwable $throwable) {
            $event->forceFill([
                'status' => OrderNotificationEventStatusEnum::Failed,
                'attempts' => $event->attempts + 1,
                'last_error' => $throwable->getMessage(),
                'failed_at' => now(),
            ])->saveQuietly();

            Log::channel('stack')->error('[ProcessOrderNotificationEventJob] notification processing failed', [
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
