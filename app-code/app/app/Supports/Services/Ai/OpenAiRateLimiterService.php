<?php

declare(strict_types=1);

namespace App\Supports\Services\Ai;

use Illuminate\Support\Facades\Cache;

final class OpenAiRateLimiterService
{
    public function throttle(): void
    {
        $app_settings = get_app_settings();
        $max_calls    = (int) data_get($app_settings, 'ai_settings.api_max_calls', (int) config('open-ai.api_max_calls', 1));
        $wait_sec     = (int) data_get($app_settings, 'ai_settings.api_wait_time_seconds', (int) config('open-ai.api_wait_time_seconds', 1));

        if ($max_calls < 1) {
            $max_calls = 1;
        }
        if ($wait_sec < 0) {
            $wait_sec = 0;
        }

        // A window of some time is enough to keep a counter between requests
        $key = config('open-ai.api_call_counter_cache_key');
        $ttl = (int) config('open-ai.api_call_counter_cache_ttl_seconds');

        $count = Cache::increment($key);

        if ($count === 1) {
            Cache::put($key, 1, $ttl);
        }

        if ($count > $max_calls) {
            if ($wait_sec > 0) {
                sleep($wait_sec);
            }

            Cache::put($key, 1, $ttl);
        }
    }
}
