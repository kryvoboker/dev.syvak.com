<?php

declare(strict_types=1);

namespace Tests\Feature\Logging;

use App\Jobs\SendTelegramLogJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsyncTelegramBotLoggingTest extends TestCase
{
    public function test_it_dispatches_telegram_log_delivery_to_the_queue(): void
    {
        Queue::fake();
        config()->set('logging.channels.monolog_async_telegram_bot.handler_with.api_key', 'test-api-key');
        config()->set('logging.channels.monolog_async_telegram_bot.handler_with.channel', 'test-channel');

        Log::channel('monolog_async_telegram_bot')->error('Queued log message.');

        Queue::assertPushed(SendTelegramLogJob::class, function (SendTelegramLogJob $job): bool {
            return $job->api_key === 'test-api-key'
                && $job->channel === 'test-channel'
                && str_contains($job->message, 'Queued log message.');
        });
    }
}
