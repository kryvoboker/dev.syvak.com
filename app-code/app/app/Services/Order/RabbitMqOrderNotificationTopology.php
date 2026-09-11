<?php

declare(strict_types=1);

namespace App\Services\Order;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

final class RabbitMqOrderNotificationTopology
{
    public function declare(AMQPChannel $channel): void
    {
        $exchange = string_value(config('order-notifications.rabbitmq.exchange'));
        $routing_key = string_value(config('order-notifications.rabbitmq.routing_key'));
        $queue = string_value(config('order-notifications.rabbitmq.queue'));
        $dead_letter_exchange = string_value(config('order-notifications.rabbitmq.dead_letter_exchange'));
        $dead_letter_queue = string_value(config('order-notifications.rabbitmq.dead_letter_queue'));

        $channel->exchange_declare($exchange, 'direct', false, true, false);
        $channel->exchange_declare($dead_letter_exchange, 'direct', false, true, false);
        $channel->queue_declare($dead_letter_queue, false, true, false, false);
        $channel->queue_bind($dead_letter_queue, $dead_letter_exchange, $routing_key);
        $channel->queue_declare(
            $queue,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-dead-letter-exchange' => $dead_letter_exchange]),
        );
        $channel->queue_bind($queue, $exchange, $routing_key);
    }
}
