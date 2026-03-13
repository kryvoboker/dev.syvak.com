<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\Catalogs\Products\Product;
use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Provides active product search and filtering logic for ProductsCarousel admin selectors.
 */
class ProductsCarouselProductSearchService
{
    public function __construct(
        private readonly ProductsCarouselConfig $products_carousel_config,
    ) {}

    /**
     * @param  array<int|string, mixed>  $category_ids
     * @return array<int, string>
     */
    public function searchActiveByCategories(string $search_query, array $category_ids): array
    {
        $started_at = microtime(true);

        $normalized_category_ids = $this->normalizeIds($category_ids);

        if ($normalized_category_ids === []) {
            return [];
        }

        $results = $this->buildBaseProductQuery(trim($search_query))
            ->whereHas('categories', function (Builder $query) use ($normalized_category_ids): void {
                $query->whereIn('categories.id', $normalized_category_ids);
            })
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

        Log::channel('daily')->info('ProductsCarousel category scoped product search executed.', [
            'query'        => $search_query,
            'categories'   => $normalized_category_ids,
            'result_count' => $results->count(),
            'elapsed_ms'   => (int) ((microtime(true) - $started_at) * 1000),
        ]);

        return $this->mapProductsToOptions($results);
    }

    /**
     * @return array<int, string>
     */
    public function searchAllActive(string $search_query): array
    {
        $started_at = microtime(true);

        $results = $this->buildBaseProductQuery(trim($search_query))
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

        Log::channel('daily')->info('ProductsCarousel global product search executed.', [
            'query'        => $search_query,
            'result_count' => $results->count(),
            'elapsed_ms'   => (int) ((microtime(true) - $started_at) * 1000),
        ]);

        return $this->mapProductsToOptions($results);
    }

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
        $normalized_product_ids  = $this->normalizeIds($product_ids);
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
     * @param  array<int|string, mixed>  $product_ids
     * @return array<int, string>
     */
    public function getLabelsByIds(array $product_ids): array
    {
        $normalized_product_ids = $this->normalizeIds($product_ids);

        if ($normalized_product_ids === []) {
            return [];
        }

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('id', $normalized_product_ids)
            ->with([
                'productDescription' => function ($query): void {
                    $query->where('language_id', $this->resolveLanguageId());
                },
            ])
            ->orderByRaw('FIELD(id, ' . implode(',', $normalized_product_ids) . ')')
            ->get();

        return $this->mapProductsToOptions($products);
    }

    public function getLabelById(?int $product_id): ?string
    {
        if (! is_int($product_id) || $product_id < 1) {
            return null;
        }

        return Arr::get($this->getLabelsByIds([$product_id]), $product_id);
    }

    private function buildBaseProductQuery(string $search_query): Builder
    {
        $language_id = $this->resolveLanguageId();

        return Product::query()
            ->select(['id', 'model', 'sku'])
            ->where('is_active', true)
            ->with([
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->when(filled($search_query), function (Builder $query) use ($search_query, $language_id): void {
                $query->where(function (Builder $query) use ($search_query, $language_id): void {
                    $query->whereLike('model', "%{$search_query}%")
                        ->orWhereLike('sku', "%{$search_query}%")
                        ->orWhereHas('productDescription', function (Builder $query) use ($search_query, $language_id): void {
                            $query
                                ->where('language_id', $language_id)
                                ->whereLike('name', "%{$search_query}%");
                        });
                });
            })
            ->orderBy('model')
            ->orderBy('id');
    }

    /**
     * @param  iterable<mixed>  $products
     * @return array<int, string>
     */
    private function mapProductsToOptions(iterable $products): array
    {
        return collect($products)
            ->filter(fn ($product): bool => $product instanceof Product)
            ->mapWithKeys(function (Product $product): array {
                $localized_name = $product->productDescription->first()?->name;

                $label_parts = array_filter([
                    is_string($localized_name) && filled($localized_name) ? $localized_name : null,
                    filled($product->model) ? '[' . $product->model . ']' : null,
                    filled($product->sku) ? '(' . $product->sku . ')' : null,
                ]);

                $label = implode(' ', $label_parts);

                if (blank($label)) {
                    $label = 'Product #' . $product->id;
                }

                return [$product->id => $label];
            })
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

    private function resolveLanguageId(): int
    {
        $locale = app()->getLocale();

        $language_by_locale = Language::query()
            ->where('is_active', true)
            ->where('code', $locale)
            ->first();

        if ($language_by_locale !== null) {
            return (int) $language_by_locale->id;
        }

        $default_language = (new Language())->getDefaultLanguage();

        if ($default_language !== null) {
            return (int) $default_language->id;
        }

        return (int) Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }
}
