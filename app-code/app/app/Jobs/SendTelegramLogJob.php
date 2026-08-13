<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SendTelegramLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $api_key,
        public readonly string $channel,
        public readonly string $message,
        public readonly bool $split_long_messages = true,
        public readonly bool $delay_between_messages = true,
        public readonly ?string $parse_mode = null,
        public readonly ?bool $disable_web_page_preview = null,
        public readonly ?bool $disable_notification = null,
        public readonly ?int $topic = null,
    ) {
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(): void
    {
        $messages = $this->splitMessages($this->message);

        foreach ($messages as $index => $message) {
            if ($this->delay_between_messages && $index > 0) {
                sleep(1);
            }

            $this->sendMessage($message);
        }
    }

    /**
     * @param Throwable $throwable
     *
     * @return void
     */
    public function failed(Throwable $throwable): void
    {
        Log::channel('daily')->error('Queued Telegram log delivery failed.', [
            'channel' => $this->channel,
            'exception' => $throwable,
        ]);

        Log::channel('monolog_telegram_bot')->error('Queued Telegram log delivery failed.', [
            'channel' => $this->channel,
            'exception' => $throwable,
        ]);
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    private function sendMessage(string $message): void
    {
        if (Str::trim($message) === '') {
            return;
        }

        $response = Http::asForm()->post(
            'https://api.telegram.org/bot' . $this->api_key . '/sendMessage',
            array_filter([
                'text' => $message,
                'chat_id' => $this->channel,
                'parse_mode' => $this->parse_mode,
                'disable_web_page_preview' => $this->disable_web_page_preview,
                'disable_notification' => $this->disable_notification,
                'message_thread_id' => $this->topic,
            ], static fn (mixed $value): bool => $value !== null),
        );

        $response->throw();

        if ($response->json('ok') !== true) {
            throw new RuntimeException((string) $response->json('description', 'Telegram API returned an unsuccessful response.'));
        }
    }

    /**
     * @param string $message
     *
     * @return string[]
     */
    private function splitMessages(string $message): array
    {
        if (!$this->split_long_messages) {
            return [Str::substr($message, 0, 4080) . (Str::length($message) > 4080 ? ' (…truncated)' : '')];
        }

        return Str::length($message) > 4096 ? mb_str_split($message, 4096, 'UTF-8') : [$message];
    }
}
