<?php

declare(strict_types=1);

namespace App\Services\Order;

use Illuminate\Support\Facades\Log;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

final readonly class RabbitMqOrderNotificationPublisher implements OrderNotificationPublisher
{
    public function __construct(
        private RabbitMqOrderNotificationConnectionFactory $connection_factory,
        private RabbitMqOrderNotificationTopology          $topology,
    ) {
    }

    /**
     * @param array<string, mixed> $envelope
     *
     * @return void
     * @throws Throwable
     * @throws JsonException
     */
    public function publish(array $envelope): void
    {
        $event_id = string_value($envelope['event_id'] ?? '');
        $order_id = integer_value($envelope['order_id'] ?? 0);

        if ($event_id === '' || $order_id < 1) {
            throw new RuntimeException('The order notification envelope has no valid identifier.');
        }
        $connection = null;
        $channel = null;

        try {
            $connection = $this->connection_factory->create();
            $channel = $connection->channel();
            $this->topology->declare($channel);

            $message = new AMQPMessage(
                json_encode($envelope, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $event_id,
                    'type' => 'order.notification',
                ],
            );

            $channel->confirm_select();
            $channel->basic_publish(
                $message,
                string_value(config('order-notifications.rabbitmq.exchange')),
                string_value(config('order-notifications.rabbitmq.routing_key')),
                true,
            );
            $channel->wait_for_pending_acks_returns(
                float_value(config('order-notifications.rabbitmq.read_write_timeout', 5)),
            );
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[RabbitMqOrderNotificationPublisher] publish failed', [
                'event_id' => $event_id,
                'order_id' => $order_id,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        } finally {
            $channel?->close();
            $connection?->close();
        }
    }
}
