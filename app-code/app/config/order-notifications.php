<?php

declare(strict_types=1);

return [
    'topic' => env('ORDER_NOTIFICATIONS_KAFKA_TOPIC', 'order-notifications'),
    'consumer_group' => env('ORDER_NOTIFICATIONS_KAFKA_CONSUMER_GROUP', 'order-notifications'),
    'schema_version' => 1,
    'telegram' => [
        'bot_token' => env('ORDER_NOTIFICATIONS_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('ORDER_NOTIFICATIONS_TELEGRAM_CHAT_ID'),
    ],
    'salesdrive' => [
        'endpoint' => env('SALESDRIVE_ORDER_ENDPOINT'),
        'token' => env('SALESDRIVE_API_TOKEN'),
    ],
    'email' => [
        'mailer' => env('ORDER_NOTIFICATIONS_MAILER', 'log'),
    ],
    'rabbitmq' => [
        'host' => env('ORDER_NOTIFICATIONS_RABBITMQ_HOST', env('RABBITMQ_HOST', 'localhost')),
        'port' => (int) env('ORDER_NOTIFICATIONS_RABBITMQ_PORT', env('RABBITMQ_PORT', 5672)),
        'username' => env('ORDER_NOTIFICATIONS_RABBITMQ_USERNAME', env('RABBITMQ_DEFAULT_USER', 'guest')),
        'password' => env('ORDER_NOTIFICATIONS_RABBITMQ_PASSWORD', env('RABBITMQ_DEFAULT_PASS', 'guest')),
        'vhost' => env('ORDER_NOTIFICATIONS_RABBITMQ_VHOST', env('RABBITMQ_DEFAULT_VHOST', '/')),
        'exchange' => env('ORDER_NOTIFICATIONS_RABBITMQ_EXCHANGE', 'order-notifications'),
        'queue' => env('ORDER_NOTIFICATIONS_RABBITMQ_QUEUE', 'order-notifications'),
        'routing_key' => env('ORDER_NOTIFICATIONS_RABBITMQ_ROUTING_KEY', 'order.notifications'),
        'dead_letter_exchange' => env('ORDER_NOTIFICATIONS_RABBITMQ_DEAD_LETTER_EXCHANGE', 'order-notifications.dlx'),
        'dead_letter_queue' => env('ORDER_NOTIFICATIONS_RABBITMQ_DEAD_LETTER_QUEUE', 'order-notifications.failed'),
        'connection_timeout' => (float) env('ORDER_NOTIFICATIONS_RABBITMQ_CONNECTION_TIMEOUT', 5),
        'read_write_timeout' => (float) env('ORDER_NOTIFICATIONS_RABBITMQ_READ_WRITE_TIMEOUT', 5),
        'heartbeat' => (int) env('ORDER_NOTIFICATIONS_RABBITMQ_HEARTBEAT', 60),
    ],
];
