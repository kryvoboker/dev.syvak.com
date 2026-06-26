<?php

declare(strict_types=1);

namespace App\Console\Commands\Cart;

use App\Models\Carts\CartItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredCartItemsCommand extends Command
{
    protected $signature = 'cart:cleanup-expired-items';

    protected $description = 'Delete cart items that are older than configured TTL by updated_at.';

    public function handle(): int
    {
        $ttl_days = max(1, (int) config('cart-modal.item_ttl_days', 30));
        $chunk_size = max(100, (int) config('cart-modal.cleanup_chunk_size', 500));
        $threshold = now(config('app.timezone'))->subDays($ttl_days);
        $deleted = 0;

        try {
            CartItem::query()
                ->where('updated_at', '<', $threshold)
                ->select('id')
                ->orderBy('id')
                ->chunkById($chunk_size, function ($rows) use (&$deleted): void {
                    $ids = $rows->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

                    if ($ids === []) {
                        return;
                    }

                    $deleted += CartItem::query()->whereIn('id', $ids)->delete();
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
}
