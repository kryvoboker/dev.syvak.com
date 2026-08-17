<?php

declare(strict_types=1);

namespace App\Logging;

use App\Jobs\SendTelegramLogJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class AsyncTelegramBotHandler extends AbstractProcessingHandler
{
    private bool $split_long_messages;

    private bool $delay_between_messages;

    public function __construct(
        private readonly string $api_key,
        private readonly string $channel,
        bool $split_long_messages = true,
        bool $delay_between_messages = true,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        private readonly ?string $parse_mode = null,
        private readonly ?bool $disable_web_page_preview = null,
        private readonly ?bool $disable_notification = null,
        private readonly ?int $topic = null,
    ) {
        parent::__construct($level, $bubble);
        $this->split_long_messages = $split_long_messages;
        $this->delay_between_messages = $delay_between_messages;
    }

    /**
     * @param LogRecord $record
     *
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        SendTelegramLogJob::dispatch(
            api_key: $this->api_key,
            channel: $this->channel,
            message: $record->formatted,
            split_long_messages: $this->split_long_messages,
            delay_between_messages: $this->delay_between_messages,
            parse_mode: $this->parse_mode,
            disable_web_page_preview: $this->disable_web_page_preview,
            disable_notification: $this->disable_notification,
            topic: $this->topic,
        );
    }
}
