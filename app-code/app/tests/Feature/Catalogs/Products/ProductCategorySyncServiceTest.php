<?php

declare(strict_types=1);

namespace Tests\Feature\Catalogs\Products;

use App\Models\Catalogs\Products\Product;
use App\Services\Catalogs\Products\ProductCategorySyncService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery\Expectation;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ProductCategorySyncServiceTest extends TestCase
{
    public function test_normalize_category_ids_returns_unique_positive_sorted_ids(): void
    {
        $service = app(ProductCategorySyncService::class);

        $normalized_ids = $service->normalizeCategoryIds([5, '2', 5, 0, -3, '8']);

        $this->assertSame([2, 5, 8], $normalized_ids);
    }

    public function test_sync_with_retry_retries_on_lock_wait_timeout_and_then_succeeds(): void
    {
        $service = app(ProductCategorySyncService::class);

        /** @var Product&MockInterface $product_mock */
        $product_mock = $this->mock(Product::class);

        /** @var BelongsToMany&MockInterface $relation_mock */
        $relation_mock = $this->mock(BelongsToMany::class);

        /** @var Expectation $get_key_expectation */
        $get_key_expectation = $product_mock->shouldReceive('getKey');
        $get_key_expectation->andReturn(672);

        /** @var Expectation $categories_expectation */
        $categories_expectation = $product_mock->shouldReceive('categories');
        $categories_expectation->andReturn($relation_mock);

        $attempt = 0;

        /** @var Expectation $sync_expectation */
        $sync_expectation = $relation_mock->shouldReceive('sync');
        $sync_expectation->andReturnUsing(function (array $category_ids) use (&$attempt): array {
            $this->assertSame([1, 2, 10], $category_ids);
            $attempt++;

            if ($attempt === 1) {
                throw new RuntimeException('SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction');
            }

            return [];
        });

        $service->syncWithRetry($product_mock, [10, '2', 1, 10]);

        $this->assertSame(2, $attempt);
    }

    public function test_sync_with_retry_throws_non_lock_exception_without_retry(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Any other error');

        $service = app(ProductCategorySyncService::class);

        /** @var Product&MockInterface $product_mock */
        $product_mock = $this->mock(Product::class);

        /** @var BelongsToMany&MockInterface $relation_mock */
        $relation_mock = $this->mock(BelongsToMany::class);

        /** @var Expectation $get_key_expectation */
        $get_key_expectation = $product_mock->shouldReceive('getKey');
        $get_key_expectation->andReturn(672);

        /** @var Expectation $categories_expectation */
        $categories_expectation = $product_mock->shouldReceive('categories');
        $categories_expectation->andReturn($relation_mock);

        /** @var Expectation $sync_expectation */
        $sync_expectation = $relation_mock->shouldReceive('sync');
        $sync_expectation->andReturnUsing(function (array $category_ids): never {
            $this->assertSame([1, 2, 10], $category_ids);
            throw new RuntimeException('Any other error');
        });

        $service->syncWithRetry($product_mock, [10, '2', 1, 10]);
    }
}
