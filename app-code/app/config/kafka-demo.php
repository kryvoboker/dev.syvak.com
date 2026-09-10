<?php

declare(strict_types=1);

return [
    'topic' => env('KAFKA_DEMO_TOPIC', 'laravel-kafka-demo'),
    'consumer_group' => env('KAFKA_DEMO_CONSUMER_GROUP', 'laravel-kafka-demo-consumer'),
];
