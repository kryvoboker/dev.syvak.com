<?php

declare(strict_types=1);

namespace App\Kafka\Consumers;

use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\Consumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

final class DemoKafkaConsumer extends Consumer
{
    public function handle(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = $message->getBody();

        if (! is_array($body)) {
            Log::channel('stack')->warning('Kafka demo received an invalid message body.', [
                'body_type' => get_debug_type($body),
                'topic' => $message->getTopicName(),
            ]);

            return;
        }

        Log::channel('daily')->info('Kafka demo message consumed.', [
            'message_id' => $body['message_id'] ?? null,
            'message' => $body['message'] ?? null,
            'topic' => $message->getTopicName(),
        ]);
    }
}
