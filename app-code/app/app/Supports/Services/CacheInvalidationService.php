<?php

declare(strict_types=1);

namespace App\Supports\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CacheInvalidationService
{
    private bool $invalidation_scheduled = false;

    public function flushAfterCommit(string $reason): void
    {
        if ($this->invalidation_scheduled) {
            return;
        }

        $this->invalidation_scheduled = true;

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function () use ($reason): void {
                $this->flushNow($reason);
            });

            return;
        }

        $this->flushNow($reason);
    }

    private function flushNow(string $reason): void
    {
        try {
            Cache::flush();

            Log::channel('daily')->info('[CacheInvalidationService] application cache invalidated', [
                'reason' => $reason,
            ]);
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('[CacheInvalidationService] application cache invalidation failed', [
                'reason' => $reason,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
