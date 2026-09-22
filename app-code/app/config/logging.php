<?php

declare(strict_types=1);

use App\Logging\AsyncTelegramBotHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'browser'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'browser' => [
            'driver' => 'daily',
            'path' => storage_path('logs/browser.log'),
            'level' => env('LOG_BROWSER_LEVEL', 'debug'),
            'days' => env('LOG_BROWSER_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'mail' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'monolog_telegram_bot' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => TelegramBotHandler::class,
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => '[%datetime%] ' . env('APP_NAME') . " - %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
                'ignoreEmptyContextAndExtra' => true,
            ],
            'processors' => [
                UidProcessor::class,
                WebProcessor::class,
                PsrLogMessageProcessor::class,
                MemoryPeakUsageProcessor::class,
                MemoryUsageProcessor::class,
            ],
            'handler_with' => [
                // Use env() here because config() lookups inside config files can resolve to null
                // when another config file has not been loaded yet.
                'apiKey' => env('MONOLOG_TELEGRAM_BOT_API_KEY'),
                'channel' => env('MONOLOG_TELEGRAM_CHAT_ID'),
                'splitLongMessages' => true,
                'delayBetweenMessages' => true,
            ],
        ],

        'monolog_async_telegram_bot' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => AsyncTelegramBotHandler::class,
            'formatter' => LineFormatter::class,
            'formatter_with' => [
                'format' => '[%datetime%] ' . env('APP_NAME') . " - %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
                'ignoreEmptyContextAndExtra' => true,
            ],
            'processors' => [
                UidProcessor::class,
                WebProcessor::class,
                PsrLogMessageProcessor::class,
                MemoryPeakUsageProcessor::class,
                MemoryUsageProcessor::class,
            ],
            'handler_with' => [
                // Use env() here because config() lookups inside config files can resolve to null
                // when another config file has not been loaded yet.
                'api_key' => env('MONOLOG_TELEGRAM_BOT_API_KEY'),
                'channel' => env('MONOLOG_TELEGRAM_CHAT_ID'),
                'split_long_messages' => true,
                'delay_between_messages' => true,
            ],
        ],

    ],

];
