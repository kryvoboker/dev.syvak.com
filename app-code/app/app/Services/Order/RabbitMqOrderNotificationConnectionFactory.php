<?php

declare(strict_types=1);

namespace App\Services\Order;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class RabbitMqOrderNotificationConnectionFactory
{
    /**
     * @return AMQPStreamConnection
     * @throws Exception
     */
    public function create(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            string_value(config('order-notifications.rabbitmq.host')),
            integer_value(config('order-notifications.rabbitmq.port', 5672)),
            string_value(config('order-notifications.rabbitmq.username')),
            string_value(config('order-notifications.rabbitmq.password')),
            string_value(config('order-notifications.rabbitmq.vhost', '/')),
            false,
            'AMQPLAIN',
            null,
            'en_US',
            float_value(config('order-notifications.rabbitmq.connection_timeout', 5)),
            float_value(config('order-notifications.rabbitmq.read_write_timeout', 5)),
            null,
            false,
            integer_value(config('order-notifications.rabbitmq.heartbeat', 60)),
        );
    }
}
