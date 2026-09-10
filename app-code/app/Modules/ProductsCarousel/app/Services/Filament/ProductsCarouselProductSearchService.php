<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services\Filament;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ProductsCarousel\Services\ProductsCarouselProductFilterService;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Provides active product-variant search and filtering logic for ProductsCarousel admin selectors.
 */
readonly class ProductsCarouselProductSearchService
{
    public function __construct(
        private ProductsCarouselConfig $products_carousel_config,
        private ProductsCarouselProductFilterService $products_carousel_product_filter_service,
    ) {
    }

    /**
     * @param  array<int|string, mixed>  $category_ids
     * @param  array<int|string, mixed>  $excluded_variant_ids
     * @return array<int, string>
     */
    public function searchActiveByCategories(
        string $search_query,
        array $category_ids,
        array $excluded_variant_ids = [],
    ): array {
        $normalized_category_ids = $this->normalizeIds($category_ids);
        $excluded_variant_ids = $this->normalizeIds($excluded_variant_ids);

        if ($normalized_category_ids === []) {
            return [];
        }

        [$search_term, $search_price] = $this->parseSearchQuery($search_query);

        $results = $this->buildBaseVariantQuery($search_term, $search_price)
            ->whereHas('product.categories', function (Builder $query) use ($normalized_category_ids): void {
                $query->whereIn('categories.id', $normalized_category_ids);
            })
            ->when($excluded_variant_ids !== [], function (Builder $query) use ($excluded_variant_ids): void {
                $query->whereNotIn('product_variants.id', $excluded_variant_ids);
            })
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

        return $this->mapVariantsToOptions($results);
    }

    /**
     * @param  array<int|string, mixed>  $excluded_variant_ids
     * @return array<int, string>
     */
    public function searchAllActive(string $search_query, array $excluded_variant_ids = []): array
    {
        $excluded_variant_ids = $this->normalizeIds($excluded_variant_ids);
        [$search_term, $search_price] = $this->parseSearchQuery($search_query);

        $results = $this->buildBaseVariantQuery($search_term, $search_price)
            ->when($excluded_variant_ids !== [], function (Builder $query) use ($excluded_variant_ids): void {
                $query->whereNotIn('product_variants.id', $excluded_variant_ids);
            })
            ->limit((int) $this->products_carousel_config->get('search.result_limit', 30))
            ->get();

        return $this->mapVariantsToOptions($results);
    }

    /**
     * @param  array<int|string, mixed>  $variant_ids
     * @return array<int>
     */
    public function filterActiveVariantIds(array $variant_ids): array
    {
        return $this->products_carousel_product_filter_service->filterActiveVariantIds($variant_ids);
    }

    /**
     * @param  array<int|string, mixed>  $product_ids
     * @return array<int>
     */
    public function filterActiveProductIds(array $product_ids): array
    {
        return $this->products_carousel_product_filter_service->filterActiveProductIds($product_ids);
    }

    /**
     * @param  array<int|string, mixed>  $product_ids
     * @param  array<int|string, mixed>  $category_ids
     * @return array<int>
     */
    public function filterActiveProductIdsByCategories(array $product_ids, array $category_ids): array
    {
        return $this->products_carousel_product_filter_service->filterActiveProductIdsByCategories(
            $product_ids,
            $category_ids,
        );
    }

    /**
     * @param  array<int|string, mixed>  $variant_ids
     * @param  array<int|string, mixed>  $category_ids
     * @return array<int>
     */
    public function filterActiveVariantIdsByCategories(array $variant_ids, array $category_ids): array
    {
        return $this->products_carousel_product_filter_service->filterActiveVariantIdsByCategories(
            $variant_ids,
            $category_ids,
        );
    }

    /**
     * @param  array<int|string, mixed>  $product_ids
     * @return array<int>
     */
    public function getActiveDefaultVariantIdsByProductIds(array $product_ids): array
    {
        return $this->products_carousel_product_filter_service->getActiveDefaultVariantIdsByProductIds($product_ids);
    }

    /**
     * @param  array<int|string, mixed>  $variant_ids
     * @return array<int, string>
     */
    public function getLabelsByIds(array $variant_ids): array
    {
        $normalized_variant_ids = $this->normalizeIds($variant_ids);

        if ($normalized_variant_ids === []) {
            return [];
        }

        $order_sql = 'FIELD(product_variants.id, ' . implode(',', $normalized_variant_ids) . ')';

        $variants = $this->buildBaseVariantQuery('', null)
            ->whereIn('product_variants.id', $normalized_variant_ids)
            // @phpstan-ignore argument.type (The IDs are normalized integers before interpolation.)
            ->orderBy(DB::raw($order_sql))
            ->get();

        return $this->mapVariantsToOptions($variants);
    }

    public function getLabelById(?int $variant_id): ?string
    {
        if (! is_int($variant_id) || $variant_id < 1) {
            return null;
        }

        return Arr::get($this->getLabelsByIds([$variant_id]), $variant_id);
    }

    /**
     * @return array{0: string, 1: float|null}
     */
    private function parseSearchQuery(string $search_query): array
    {
        $search_query = Str::trim($search_query);

        if (preg_match('/^(.*?)\\s*==\\s*([0-9]+(?:[.,][0-9]+)?)\\s*$/u', $search_query, $matches) !== 1) {
            return [$search_query, null];
        }

        $search_term = Str::trim($matches[1]);
        $price = (float) Str::replace(',', '.', $matches[2]);

        return [$search_term, $price];
    }

    /** @return Builder<ProductVariant> */
    private function buildBaseVariantQuery(string $search_term, ?float $search_price): Builder
    {
        $language_id = $this->resolveLanguageId();

        return ProductVariant::query()
            ->select('product_variants.*')
            ->where('product_variants.is_active', true)
            ->whereHas('product', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->with([
                'product' => function ($query) use ($language_id): void {
                    $query->with([
                        'productDescription' => function ($description_query) use ($language_id): void {
                            $description_query->where('language_id', $language_id);
                        },
                        'variants' => function ($variant_query): void {
                            $variant_query->orderBy('sort_order')->orderBy('id');
                        },
                    ]);
                },
                'descriptions' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'discounts' => function ($query): void {
                    $this->applyCurrentDiscountScope($query);
                    $query->orderBy('priority')->orderByDesc('updated_at');
                },
            ])
            ->when(filled($search_term), function (Builder $query) use ($search_term): void {
                $query->where(function (Builder $query) use ($search_term): void {
                    $query
                        ->whereHas('product', function (Builder $product_query) use ($search_term): void {
                            $product_query
                                ->whereLike('model', "%$search_term%")
                                ->orWhereLike('sku', "%$search_term%")
                                ->orWhereLike('ean', "%$search_term%");
                        })
                        ->orWhereHas('product.productDescription', function (Builder $description_query) use ($search_term): void {
                            $description_query->whereLike('name', "%$search_term%");
                        })
                        ->orWhereHas('descriptions', function (Builder $description_query) use ($search_term): void {
                            $description_query->whereLike('name', "%$search_term%");
                        });
                });
            })
            ->when($search_price !== null, function (Builder $query) use ($search_price): void {
                $query->where(function (Builder $query) use ($search_price): void {
                    $query
                        ->where('product_variants.price', $search_price)
                        ->orWhereHas('discounts', function (Builder $discount_query) use ($search_price): void {
                            $this->applyCurrentDiscountScope($discount_query);
                            $discount_query->where('price', $search_price);
                        });
                });
            })
            ->orderBy('product_variants.product_id')
            ->orderBy('product_variants.sort_order')
            ->orderBy('product_variants.id');
    }

    /** @param Builder<\Illuminate\Database\Eloquent\Model>|Relation<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, mixed> $query */
    private function applyCurrentDiscountScope(Builder|Relation $query): void
    {
        $current_date_time = now(config('app.timezone'));
        $user_group_id = get_app_settings()?->user_group_id;

        $query
            ->where('date_start', '<=', $current_date_time)
            ->where('date_end', '>=', $current_date_time);

        if ($user_group_id === null) {
            $query->whereNull('user_group_id');

            return;
        }

        $query->where('user_group_id', $user_group_id);
    }

    /**
     * @param  iterable<int, mixed>  $variants
     * @return array<int, string>
     */
    private function mapVariantsToOptions(iterable $variants): array
    {
        return collect($variants)
            ->filter(fn (mixed $variant): bool => $variant instanceof ProductVariant)
            ->mapWithKeys(function (ProductVariant $variant): array {
                $product = $variant->product;
                $product_name = $product?->productDescription?->first()?->name;
                $variant_number = $product?->variants?->search(
                    fn (ProductVariant $product_variant): bool => $product_variant->id === $variant->id,
                );
                $variant_number = is_int($variant_number) ? $variant_number + 1 : 1;
                $variant_name = __('productscarousel::admin/modules/module_instances.products_carousel.labels.variant', [
                    'number' => $variant_number,
                ]);

                if ($variant->is_default) {
                    $variant_name .= ' ' . __('productscarousel::admin/modules/module_instances.products_carousel.labels.default_variant');
                }
                $price = format_price(
                    (float) $variant->price,
                    config('app.currency.current_currency_code'),
                    (float) config('app.currency.default_exchange_rate'),
                );
                $discount = $variant->discounts->first();
                $discount_label = $discount === null
                    ? null
                    : __('productscarousel::admin/modules/module_instances.products_carousel.labels.discount_price', [
                        'price' => format_price(
                            (float) $discount->price,
                            config('app.currency.current_currency_code'),
                            (float) config('app.currency.default_exchange_rate'),
                        ),
                    ]);

                $label_parts = array_filter([
                    is_string($product_name) && filled($product_name) ? $product_name : null,
                    filled($product?->model) ? '[' . $product->model . ']' : null,
                    filled($product?->sku) ? '(' . $product->sku . ')' : null,
                    filled($product?->ean) ? '{EAN: ' . $product->ean . '}' : null,
                    $variant_name,
                    $price,
                    $discount_label,
                ]);

                /** @var array<int, string> $label_parts */
                return [(int) $variant->id => implode(' ', $label_parts) ?: 'Variant #' . $variant->id];
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
