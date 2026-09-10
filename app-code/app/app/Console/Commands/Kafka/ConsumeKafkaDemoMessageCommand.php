<?php

declare(strict_types=1);

namespace App\Console\Commands\Kafka;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;

class ConsumeKafkaDemoMessageCommand extends Command
{
    protected $signature = 'kafka:demo:consume
        {--topic= : Kafka topic; defaults to the configured demo topic}
        {--group= : Consumer group; defaults to the configured demo group}';

    protected $description = 'Consume one demonstration message from Kafka.';

    public function handle(): int
    {
        $topic = (string) ($this->option('topic') ?: config('kafka-demo.topic'));
        $group = (string) ($this->option('group') ?: config('kafka-demo.consumer_group'));

        Kafka::consumer([$topic], $group, (string) config('kafka.brokers'))
            ->withOption('auto.offset.reset', 'earliest')
            ->withMaxMessages(1)
            ->stopAfterLastMessage()
            ->withHandler(function (ConsumerMessage $message, MessageConsumer $consumer): void {
                $body = $message->getBody();

                if (! is_array($body)) {
                    Log::channel('stack')->warning('Kafka demo received an invalid message body.', [
                        'body_type' => get_debug_type($body),
                    ]);

                    $this->warn('Received a message with an invalid body.');

                    return;
                }

                $this->line((string) json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                Log::channel('daily')->info('Kafka demo message consumed.', [
                    'message_id' => $body['message_id'] ?? null,
                    'topic' => $message->getTopicName(),
                ]);
            })
            ->build()
            ->consume();

        return self::SUCCESS;
    }
}
