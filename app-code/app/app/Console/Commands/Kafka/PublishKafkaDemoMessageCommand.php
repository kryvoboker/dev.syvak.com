<?php

declare(strict_types=1);

namespace App\Console\Commands\Kafka;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class PublishKafkaDemoMessageCommand extends Command
{
    protected $signature = 'kafka:demo:publish
        {--message=Hello Kafka : Message text to publish}
        {--topic= : Kafka topic; defaults to the configured demo topic}';

    protected $description = 'Publish a JSON demonstration message to Kafka.';

    public function handle(): int
    {
        $topic = (string) ($this->option('topic') ?: config('kafka-demo.topic'));
        $message = (string) $this->option('message');
        $message_id = (string) Str::uuid();

        Kafka::publish((string) config('kafka.brokers'))
            ->onTopic($topic)
            ->withMessage(new Message(
                body: [
                    'event' => 'kafka.demo.message',
                    'message_id' => $message_id,
                    'message' => $message,
                    'published_at' => now()->toIso8601String(),
                ],
                key: $message_id,
            ))
            ->send();

        $this->info("Published message {$message_id} to {$topic}.");

        return self::SUCCESS;
    }
}
