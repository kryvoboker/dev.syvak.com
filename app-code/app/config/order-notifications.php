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
];
