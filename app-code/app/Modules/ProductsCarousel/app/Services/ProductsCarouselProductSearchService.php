<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Provides active product search and filtering logic for ProductsCarousel admin selectors.
 */
readonly class ProductsCarouselProductSearchService
{
    public function __construct(
        private ProductsCarouselConfig $products_carousel_config,
    ) {
    }

    /**
     * @param  array<int|string, mixed>  $category_ids
     * @param  array<int|string, mixed>  $excluded_product_ids
     * @return array<int, string>
     */
    public function searchActiveByCategories(
        string $search_query,
        array $category_ids,
        array $excluded_product_ids = [],
    ): array {
        $normalized_category_ids = $this->normalizeIds($category_ids);
        $excluded_product_ids = $this->normalizeIds($excluded_product_ids);

        if ($normalized_category_ids === []) {
            return [];
        }

        $results = $this->buildBaseProductQuery(trim($search_query))
            ->whereHas('categories', function (Builder $query) use ($normalized_category_ids): void {
                $query->whereIn('categories.id', $normalized_category_ids);
            })
            ->when($excluded_product_ids !== [], function (Builder $query) use ($excluded_product_ids): void {
                $query->whereNotIn('id', $excluded_product_ids);
            })
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

        return $this->mapProductsToOptions($results);
    }

    /**
     * @param  array<int|string, mixed>  $excluded_product_ids
     * @return array<int, string>
     */
    public function searchAllActive(string $search_query, array $excluded_product_ids = []): array
    {
        $excluded_product_ids = $this->normalizeIds($excluded_product_ids);

        $results = $this->buildBaseProductQuery(trim($search_query))
            ->when($excluded_product_ids !== [], function (Builder $query) use ($excluded_product_ids): void {
                $query->whereNotIn('id', $excluded_product_ids);
            })
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

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
        return Product::query()
            ->select(['id', 'model', 'sku'])
            ->where('is_active', true)
            ->with(['productDescription'])
            ->when(filled($search_query), function (Builder $query) use ($search_query): void {
                $query->where(function (Builder $query) use ($search_query): void {
                    $query->whereLike('model', "%$search_query%")
                        ->orWhereLike('sku', "%$search_query%")
                        ->orWhereHas('productDescription', function (Builder $query) use ($search_query): void {
                            $query
                                ->whereLike('name', "%$search_query%");
                        });
                });
            })
            ->orderBy('model')
            ->orderBy('id');
    }

    /**
     * @param  array  $products
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
