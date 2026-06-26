<?php

declare(strict_types=1);

namespace App\Services\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Synchronizes product categories with retry handling for lock wait timeout errors.
 */
class ProductCategorySyncService
{
    /**
     * @param  array<int|string, mixed>|mixed  $category_ids
     *
     * @throws Throwable
     */
    public function syncWithRetry(Product $product, mixed $category_ids): void
    {
        $normalized_category_ids = $this->normalizeCategoryIds($category_ids);
        $max_attempts = 3;
        $product_id = (int) $product->getKey();

        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            try {
                $product->categories()->sync($normalized_category_ids);

                Log::channel('daily')->info('[FIX:product-categories-lock] Product categories synced.', [
                    'product_id' => $product_id,
                    'categories_count' => count($normalized_category_ids),
                    'attempt' => $attempt,
                ]);

                return;
            } catch (Throwable $throwable) {
                if (! $this->isLockWaitTimeoutException($throwable) || $attempt === $max_attempts) {
                    Log::channel('stack')->error('[FIX:product-categories-lock] Product category sync failed.', [
                        'product_id' => $product_id,
                        'attempt' => $attempt,
                        'exception' => $throwable,
                    ]);

                    throw $throwable;
                }

                Log::channel('stack')->warning('[FIX:product-categories-lock] Retrying product category sync after lock timeout.', [
                    'product_id' => $product_id,
                    'attempt' => $attempt,
                ]);

                usleep($attempt * 200_000);
            }
        }
    }

    /**
     * @param  array<int|string, mixed>|mixed  $category_ids
     * @return array<int>
     */
    public function normalizeCategoryIds(mixed $category_ids): array
    {
        if (! is_array($category_ids)) {
            $category_ids = [$category_ids];
        }

        return collect($category_ids)
            ->map(fn (mixed $category_id): int => (int) $category_id)
            ->filter(fn (int $category_id): bool => $category_id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function isLockWaitTimeoutException(Throwable $throwable): bool
    {
        return Str::contains(
            $throwable->getMessage(),
            ['Lock wait timeout exceeded', 'SQLSTATE[HY000]: General error: 1205'],
        );
    }
}
