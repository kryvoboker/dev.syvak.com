<?php

declare(strict_types=1);

namespace App\Services\Modules;

use Closure;
use Illuminate\Support\Facades\Cache;

class ModuleCacheService
{
    public const VERSION_CACHE_KEY = 'modules:cache:version';

    public function remember(string $key, Closure $callback, int $ttl_seconds = 3600): mixed
    {
        return Cache::remember(
            $this->buildKey($key),
            now()->addSeconds($ttl_seconds),
            $callback,
        );
    }

    public function flush(): void
    {
        Cache::increment(self::VERSION_CACHE_KEY);
    }

    public function buildKey(string $suffix): string
    {
        return 'modules:v' . $this->getVersion() . ':' . $suffix;
    }

    public function getVersion(): int
    {
        $version = Cache::get(self::VERSION_CACHE_KEY);

        if (! is_int($version)) {
            Cache::forever(self::VERSION_CACHE_KEY, 1);

            return 1;
        }

        return $version;
    }
}
