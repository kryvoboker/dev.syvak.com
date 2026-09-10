<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Tests\TestCase;

class KafkaDemoCommandsTest extends TestCase
{
    public function test_publish_command_publishes_the_demo_message(): void
    {
        Kafka::fake();

        $exit_code = Artisan::call('kafka:demo:publish', [
            '--topic' => 'test-kafka-demo',
            '--message' => 'Test message',
        ]);

        self::assertSame(0, $exit_code);
        Kafka::assertPublishedOn('test-kafka-demo', null, function (Message $message): bool {
            $body = $message->getBody();

            return is_array($body)
                && $body['event'] === 'kafka.demo.message'
                && $body['message'] === 'Test message';
        });
    }
}
