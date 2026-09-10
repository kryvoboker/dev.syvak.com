<?php

declare(strict_types=1);

namespace App\Supports\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class StorefrontCacheService
{
    private const string PREFIX = 'storefront:';

    /** @param Closure(): mixed $callback */
    public function remember(string $key, Closure $callback, int $ttl_seconds = 300): mixed
    {
        return Cache::remember(
            self::PREFIX . Str::trim($key),
            now()->addSeconds($ttl_seconds),
            static fn (): mixed => $callback(),
        );
    }
}
