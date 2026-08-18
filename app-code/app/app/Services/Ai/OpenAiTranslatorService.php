<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Supports\Services\Ai\OpenAiRateLimiterService;
use Illuminate\Support\Str;
use OpenAI\Client;
use RuntimeException;
use Throwable;

final readonly class OpenAiTranslatorService
{
    public function __construct(
        private Client $client,
        private OpenAiRateLimiterService $rate_limiter,
    ) {
    }

    /**
     * @throws RuntimeException
     */
    public function translate(string $prompt): string
    {
        $app_settings = get_app_settings();
        $model = $this->stringValue(data_get($app_settings, 'ai_settings.api_model', $this->stringValue(config('open-ai.api_model'))));
        $max_tokens = $this->integerValue(data_get($app_settings, 'ai_settings.api_max_tokens', $this->integerValue(config('open-ai.api_max_tokens'))));
        $system = $this->stringValue(data_get($app_settings, 'ai_settings.system_prompt', $this->stringValue(config('open-ai.system_prompt'))));
        $max_retries = $this->integerValue(data_get($app_settings, 'ai_settings.api_max_retries', $this->integerValue(config('open-ai.api_max_retries'))));
        $max_retry_wait_time_seconds = $this->integerValue(data_get($app_settings, 'ai_settings.api_max_retry_wait_time_seconds', $this->integerValue(config('open-ai.api_max_retry_wait_time_seconds'))));
        $fallback_wait = $this->integerValue(data_get($app_settings, 'ai_settings.api_wait_time_seconds', $this->integerValue(config('open-ai.api_wait_time_seconds'))));

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                // standard wait (according to settings) after N requests
                $this->rate_limiter->throttle();

                $resp = $this->client->chat()->create([
                    'model' => $model,
                    'max_completion_tokens' => $max_tokens,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

                $text = $resp->choices[0]->message->content ?? null;
                $text = is_string($text) ? Str::trim($text) : '';

                if ($text === '') {
                    throw new RuntimeException('OpenAI returned empty translation.');
                }

                return $text;
            } catch (Throwable $e) {
                $retry_after = $this->extractRetryAfterSeconds($e);

                // If this is the limit and we are given a Retry-After, we wait (but not more than $max_retry_wait_time_seconds sec)
                if ($retry_after !== null && $retry_after > 0 && $retry_after <= $max_retry_wait_time_seconds && $attempts < $max_retries) {
                    sleep($retry_after);

                    continue;
                }

                // Otherwise, the usual retry up to $max_retries attempts (with a pause from the settings)
                if ($attempts < $max_retries) {
                    if ($fallback_wait > 0 && $fallback_wait <= $max_retry_wait_time_seconds) {
                        sleep($fallback_wait);
                    }

                    continue;
                }

                throw new RuntimeException(
                    "OpenAI translation failed after $max_retries attempts: " . $e->getMessage(),
                    (int) $e->getCode(),
                    $e,
                );
            }
        }
    }

    /**
     * We try to extract "Retry-After" either from the response or from the error text.
     */
    private function extractRetryAfterSeconds(Throwable $e): ?int
    {
        // 1) If the library provides access to response headers (depending on the version)
        if (method_exists($e, 'getResponse')) {
            $resp = $e->getResponse();

            if (is_object($resp) && method_exists($resp, 'getHeaderLine')) {
                $header_value = $resp->getHeaderLine('Retry-After');
                $ra = is_scalar($header_value) ? (string) $header_value : '';

                if ($ra !== '' && ctype_digit($ra)) {
                    return (int) $ra;
                }
            }
        }

        // 2) According to (best-effort)
        $msg = $e->getMessage();
        $m = Str::match('~retry[- ]after[: ]+(\d+)~i', $msg);

        if (! empty($m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
