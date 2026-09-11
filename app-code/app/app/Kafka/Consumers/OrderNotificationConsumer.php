<?php

declare(strict_types=1);

namespace App\Kafka\Consumers;

use App\Services\Order\OrderNotificationPublisher;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use RuntimeException;
use Throwable;

final class OrderNotificationConsumer extends Consumer
{
    public function __construct(private readonly OrderNotificationPublisher $publisher)
    {
    }

    /** @throws Throwable */
    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        unset($consumer);

        $body = $message->getBody();

        if (! is_array($body)) {
            throw new RuntimeException('Kafka order notification message must contain an object body.');
        }

        $event_id = string_value(Arr::get($body, 'event_id', ''));
        $order_id = integer_value(Arr::get($body, 'order_id', 0));
        $payload = Arr::get($body, 'payload');

        if ($event_id === '' || $order_id < 1 || ! is_array($payload)) {
            Log::channel('stack')->critical('[OrderNotificationConsumer] invalid Kafka envelope', [
                'event_id' => $event_id,
                'order_id' => $order_id,
            ]);

            throw new RuntimeException('Kafka order notification envelope is invalid.');
        }

        $this->publisher->publish(string_keyed_array($body));
    }
}
