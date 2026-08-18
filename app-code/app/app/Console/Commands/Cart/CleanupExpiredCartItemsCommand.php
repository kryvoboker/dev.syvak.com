<?php

declare(strict_types=1);

namespace App\Console\Commands\Cart;

use App\Models\Carts\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredCartItemsCommand extends Command
{
    protected $signature = 'cart:cleanup-expired-items';

    protected $description = 'Delete cart items that are older than configured TTL by updated_at.';

    public function handle(): int
    {
        $ttl_days = max(1, $this->integerValue(config('cart-modal.item_ttl_days', 30)));
        $chunk_size = max(100, $this->integerValue(config('cart-modal.cleanup_chunk_size', 500)));
        $timezone = config('app.timezone');
        $threshold = now(is_scalar($timezone) ? (string) $timezone : null)->subDays($ttl_days);
        $deleted = 0;

        try {
            Cart::query()
                ->where('updated_at', '<', $threshold)
                ->select('id')
                ->orderBy('id')
                ->chunkById($chunk_size, function ($rows) use (&$deleted): void {
                    $ids = $rows->pluck('id')->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)->all();

                    if ($ids === []) {
                        return;
                    }

                    $deleted_result = Cart::query()->whereIn('id', $ids)->delete();
                    $deleted += is_numeric($deleted_result) ? (int) $deleted_result : 0;
                });

            Log::channel('daily')->info('Expired cart items cleanup completed.', [
                'deleted_rows' => $deleted,
                'ttl_days' => $ttl_days,
            ]);

            $this->info("Deleted rows: {$deleted}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::channel('stack')->error('Expired cart items cleanup failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $this->error('Expired cart items cleanup failed.');

            return self::FAILURE;
        }
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
