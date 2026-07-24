<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides shared active-product filtering for admin selectors and storefront payloads.
 */
final readonly class ProductsCarouselProductFilterService
{
    /**
     * @param  array<int|string, mixed>  $product_ids
     * @return array<int>
     */
    public function filterActiveProductIds(array $product_ids): array
    {
        $normalized_product_ids = $this->normalizeIds($product_ids);

        if ($normalized_product_ids === []) {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->whereIn('id', $normalized_product_ids)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $product_ids
     * @param  array<int|string, mixed>  $category_ids
     * @return array<int>
     */
    public function filterActiveProductIdsByCategories(array $product_ids, array $category_ids): array
    {
        $normalized_product_ids = $this->normalizeIds($product_ids);
        $normalized_category_ids = $this->normalizeIds($category_ids);

        if ($normalized_product_ids === [] || $normalized_category_ids === []) {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->whereIn('id', $normalized_product_ids)
            ->whereHas('categories', function (Builder $query) use ($normalized_category_ids): void {
                $query->whereIn('categories.id', $normalized_category_ids);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $variant_ids
     * @return array<int>
     */
    public function filterActiveVariantIds(array $variant_ids): array
    {
        $normalized_variant_ids = $this->normalizeIds($variant_ids);

        if ($normalized_variant_ids === []) {
            return [];
        }

        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->whereIn('id', $normalized_variant_ids)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $variant_ids
     * @param  array<int|string, mixed>  $category_ids
     * @return array<int>
     */
    public function filterActiveVariantIdsByCategories(array $variant_ids, array $category_ids): array
    {
        $normalized_variant_ids = $this->normalizeIds($variant_ids);
        $normalized_category_ids = $this->normalizeIds($category_ids);

        if ($normalized_variant_ids === [] || $normalized_category_ids === []) {
            return [];
        }

        return ProductVariant::query()
            ->where('is_active', true)
            ->whereIn('id', $normalized_variant_ids)
            ->whereHas('product', function (Builder $query) use ($normalized_category_ids): void {
                $query
                    ->where('is_active', true)
                    ->whereHas('categories', function (Builder $category_query) use ($normalized_category_ids): void {
                        $category_query->whereIn('categories.id', $normalized_category_ids);
                    });
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $product_ids
     * @return array<int>
     */
    public function getActiveDefaultVariantIdsByProductIds(array $product_ids): array
    {
        $normalized_product_ids = $this->normalizeIds($product_ids);

        if ($normalized_product_ids === []) {
            return [];
        }

        return ProductVariant::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->whereIn('product_id', $normalized_product_ids)
            ->whereHas('product', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $ids
     * @return array<int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
