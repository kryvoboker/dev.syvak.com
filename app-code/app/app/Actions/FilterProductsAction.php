<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CatalogFilter\CatalogFilterDiscountOnlyPolicyEnum;
use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterPriceSourceModeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Models\Catalogs\CatalogFilter\CatalogFilterValue;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Services\Catalogs\CatalogFilter\PriceSourceResolverService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;

readonly class FilterProductsAction
{
    public function __construct(
        private PageSettingsBootstrapService $page_settings_bootstrap_service,
        private PriceSourceResolverService   $price_source_resolver_service,
    ) {}

    /**
     * @param array<string, mixed> $validated_data
     *
     * @return array<string, mixed>
     * @throws Throwable
     *
     */
    public function handle(array $validated_data, string $category_slug, ?string $locale = null): array
    {
        $locale   = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);
        $per_page = $this->resolveCategoryProductsPerPage();

        if (!$language instanceof Language) {
            return $this->buildEmptyResponse($per_page, $validated_data, 'default');
        }

        $category = Category::findBySlug($category_slug, (int)$language->id);

        if (!$category instanceof Category) {
            return $this->buildEmptyResponse($per_page, $validated_data, 'default');
        }

        $filter_set = $this->resolveActiveCategoryFilterSet();

        $is_filter_mechanism_enabled = $filter_set instanceof CatalogFilterSet && $filter_set->is_enabled;
        $minimum_stock_quantity      = $this->resolveMinimumStockQuantity($filter_set);
        $filter_groups               = $is_filter_mechanism_enabled
            ? $this->resolveEnabledFilterGroups($filter_set)
            : collect();

        $requested_sort_value       = (string)Arr::get($validated_data, 'sort', '');
        $resolved_sort_code         = $this->resolveSortCodeFromRequestedValue($requested_sort_value);
        $effective_price_expression = $this->resolveEffectivePriceSqlExpression($filter_set);

        $products_query = $this->buildBaseProductsQuery(
            filter_set            : $filter_set,
            category_id           : (int)$category->id,
            language_id           : (int)$language->id,
            minimum_stock_quantity: $minimum_stock_quantity,
        );

        if ($is_filter_mechanism_enabled) {
            $products_query = $this->applyAttributeFilters(
                query         : $products_query,
                filter_set    : $filter_set,
                filter_groups : $filter_groups,
                validated_data: $validated_data,
            );

            $products_query = $this->applyPriceRangeFilter(
                query                     : $products_query,
                filter_set                : $filter_set,
                filter_groups             : $filter_groups,
                validated_data            : $validated_data,
                effective_price_expression: $effective_price_expression,
            );
        }

        $this->applySorting(
            query                     : $products_query,
            resolved_sort_code        : $resolved_sort_code,
            effective_price_expression: $effective_price_expression,
        );

        $products = $products_query
            ->paginate($per_page)
            ->withQueryString();

        return [
            'products'          => $this->mapProductsForResponse(
                products              : $products,
                language_id           : (int)$language->id,
                filter_set            : $filter_set,
                minimum_stock_quantity: $minimum_stock_quantity,
            ),
            'pagination'        => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'from'         => $products->firstItem(),
                'to'           => $products->lastItem(),
            ],
            'applied_filters'   => [
                'sort'       => $requested_sort_value,
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'stock'      => (array)Arr::get($validated_data, 'stock', []),
                'attributes' => (array)Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'  => $resolved_sort_code,
            'is_filter_enabled' => $is_filter_mechanism_enabled,
        ];
    }

    /**
     * This method uses the same filter contract as `handle()` but returns only
     * matched products count for selected category/filter params.
     *
     * @param array<string, mixed> $validated_data
     *
     * @throws Throwable
     */
    public function count(array $validated_data, string $category_slug, ?string $locale = null): int
    {
        $response_data = $this->handle($validated_data, $category_slug, $locale);

        return (int)Arr::get($response_data, 'pagination.total', 0);
    }

    /**
     * @throws Throwable
     */
    private function resolveCategoryProductsPerPage(): int
    {
        $category_page_setting = $this->page_settings_bootstrap_service->bootstrapCategoryPageSetting();
        $page_settings         = get_page_settings($category_page_setting);

        return ProductsLimitService::getProductsCategoryLimit($page_settings);
    }

    private function resolveActiveCategoryFilterSet(): ?CatalogFilterSet
    {
        /** @var CatalogFilterSet|null $filter_set */
        $filter_set = CatalogFilterSet::query()
            ->where('is_enabled', true)
            ->where(function (Builder $query): void {
                $query
                    ->where('context_type', 'category')
                    ->orWhereJsonContains('context_types', 'category');
            })
            ->orderBy('id')
            ->first();

        if (!$filter_set instanceof CatalogFilterSet) {
            return null;
        }

        $filter_set->loadMissing([
            'groups' => function ($query): void {
                $query
                    ->where('is_enabled', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'values' => function ($values_query): void {
                            $values_query
                                ->where('is_enabled', true)
                                ->orderBy('sort_order')
                                ->orderBy('id');
                        },
                    ]);
            },
        ]);

        return $filter_set;
    }

    /**
     * @return Collection<int, CatalogFilterGroup>
     */
    private function resolveEnabledFilterGroups(CatalogFilterSet $filter_set): Collection
    {
        /** @var Collection<int, CatalogFilterGroup> $groups */
        $groups = $filter_set->groups;

        return $groups
            ->filter(function (CatalogFilterGroup $group) use ($filter_set): bool {
                if ((string)$group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Price->value) {
                    return (bool)$filter_set->is_price_filter_enabled;
                }

                if ((string)$group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value) {
                    return (bool)$filter_set->is_attribute_filtering_enabled;
                }

                return true;
            })
            ->values();
    }

    private function buildBaseProductsQuery(
        ?CatalogFilterSet $filter_set,
        int               $category_id,
        int               $language_id,
        int               $minimum_stock_quantity,
    ): Builder {
        $app_settings     = get_app_settings();
        $current_datetime = now(config('app.timezone'));

        $query = Product::query()
            ->select('products.*')
            ->selectRaw(config('database.prefix') . 'active_product_discount.price as active_discount_price')
            ->with([
                'slugs'              => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->leftJoin('product_discounts as active_product_discount', function (JoinClause $join) use ($app_settings, $current_datetime): void {
                $join
                    ->on('active_product_discount.product_id', '=', 'products.id')
                    ->where('active_product_discount.user_group_id', '=', (int)$app_settings->user_group_id)
                    ->where('active_product_discount.date_start', '<=', $current_datetime)
                    ->where('active_product_discount.date_end', '>=', $current_datetime);
            })
            ->where('products.is_active', true)
            ->where('products.quantity', '>=', $minimum_stock_quantity)
            ->whereHas('categories', function ($query) use ($category_id): void {
                $query->where('categories.id', $category_id);
            });

        if (
            $filter_set instanceof CatalogFilterSet
            && $this->resolvePriceSourceMode($filter_set) === CatalogFilterPriceSourceModeEnum::DiscountOnly
            && $this->resolveDiscountOnlyPolicy($filter_set) === CatalogFilterDiscountOnlyPolicyEnum::ExcludeWithoutDiscount
        ) {
            $query->whereNotNull('active_product_discount.price');
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $validated_data
     */
    private function applyAttributeFilters(
        Builder          $query,
        CatalogFilterSet $filter_set,
        Collection       $filter_groups,
        array            $validated_data,
    ): Builder {
        if (!$filter_set->is_attribute_filtering_enabled) {
            return $query;
        }

        $attribute_filters = Arr::get($validated_data, 'attributes', []);

        if (!is_array($attribute_filters) || $attribute_filters === []) {
            return $query;
        }

        $attribute_groups_by_id = $filter_groups
            ->where(fn(CatalogFilterGroup $group): bool => (string)$group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value)
            ->keyBy(fn(CatalogFilterGroup $group): int => (int)$group->source_id);

        foreach ($attribute_filters as $attribute_id => $selected_codes) {
            $attribute_id = (int)$attribute_id;

            if ($attribute_id <= 0) {
                continue;
            }

            /** @var CatalogFilterGroup|null $attribute_group */
            $attribute_group = $attribute_groups_by_id->get($attribute_id);

            if (!$attribute_group instanceof CatalogFilterGroup) {
                continue;
            }

            $selected_codes = collect(is_array($selected_codes) ? $selected_codes : [$selected_codes])
                ->map(fn(mixed $code): string => (string)$code)
                ->filter(fn(string $code): bool => filled($code))
                ->unique()
                ->values();

            if ($selected_codes->isEmpty()) {
                continue;
            }

            $attribute_values = CatalogFilterValue::query()
                ->where('catalog_filter_group_id', (int)$attribute_group->id)
                ->where('is_enabled', true)
                ->whereIn('code', $selected_codes->all())
                ->pluck('value_string')
                ->map(fn(mixed $value): string => (string)$value)
                ->filter(fn(string $value): bool => filled($value))
                ->unique()
                ->values();

            if ($attribute_values->isEmpty()) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $query->whereHas('productToAttribute', function ($attribute_query) use ($attribute_id, $attribute_values): void {
                $attribute_query
                    ->where('attribute_id', $attribute_id)
                    ->whereIn('text', $attribute_values->all());
            });
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $validated_data
     */
    private function applyPriceRangeFilter(
        Builder          $query,
        CatalogFilterSet $filter_set,
        Collection       $filter_groups,
        array            $validated_data,
        string           $effective_price_expression,
    ): Builder {
        if (!$filter_set->is_price_filter_enabled) {
            return $query;
        }

        /** @var CatalogFilterGroup|null $price_group */
        $price_group = $filter_groups
            ->first(fn(CatalogFilterGroup $group): bool => (string)$group->code === 'price');

        if (!$price_group instanceof CatalogFilterGroup) {
            return $query;
        }

        $price_from = Arr::get($validated_data, 'price_from');
        $price_to   = Arr::get($validated_data, 'price_to');

        if (is_numeric($price_from)) {
            $query->whereRaw($effective_price_expression . ' >= ?', [(float)$price_from]);
        }

        if (is_numeric($price_to)) {
            $query->whereRaw($effective_price_expression . ' <= ?', [(float)$price_to]);
        }

        return $query;
    }

    private function applySorting(Builder $query, string $resolved_sort_code, string $effective_price_expression): void
    {
        match ($resolved_sort_code) {
            'bestsellers' => $query
                ->orderByDesc('products.viewed')
                ->orderByDesc('products.id'),

            'price_asc'   => $query
                ->orderByRaw($effective_price_expression . ' ASC')
                ->orderByDesc('products.id'),

            'price_desc'  => $query
                ->orderByRaw($effective_price_expression . ' DESC')
                ->orderByDesc('products.id'),

            default       => $query
                ->orderByDesc('products.date_added')
                ->orderByDesc('products.id'),
        };
    }

    /**
     * @throws Throwable
     */
    private function resolveSortCodeFromRequestedValue(string $requested_sort_value): string
    {
        $page_setting = $this->page_settings_bootstrap_service->bootstrapCategoryPageSetting();

        return resolve_sort_code($page_setting, $requested_sort_value);
    }

    private function resolveEffectivePriceSqlExpression(?CatalogFilterSet $filter_set): string
    {
        $price_source_mode    = $this->resolvePriceSourceMode($filter_set);
        $discount_only_policy = $this->resolveDiscountOnlyPolicy($filter_set);

        if ($price_source_mode === CatalogFilterPriceSourceModeEnum::RrcOnly) {
            return 'products.price';
        }

        if ($price_source_mode === CatalogFilterPriceSourceModeEnum::Both) {
            return 'COALESCE(active_product_discount.price, products.price)';
        }

        if ($discount_only_policy === CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase) {
            return 'COALESCE(active_product_discount.price, products.price)';
        }

        return 'active_product_discount.price';
    }

    private function resolvePriceSourceMode(?CatalogFilterSet $filter_set): CatalogFilterPriceSourceModeEnum
    {
        if (!$filter_set instanceof CatalogFilterSet) {
            return CatalogFilterPriceSourceModeEnum::Both;
        }

        $price_source_mode = $filter_set->price_source_mode;

        if ($price_source_mode instanceof CatalogFilterPriceSourceModeEnum) {
            return $price_source_mode;
        }

        return CatalogFilterPriceSourceModeEnum::tryFrom((string)$price_source_mode)
            ?? CatalogFilterPriceSourceModeEnum::Both;
    }

    private function resolveDiscountOnlyPolicy(?CatalogFilterSet $filter_set): CatalogFilterDiscountOnlyPolicyEnum
    {
        if (!$filter_set instanceof CatalogFilterSet) {
            return CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase;
        }

        $discount_only_policy = $filter_set->discount_only_policy;

        if ($discount_only_policy instanceof CatalogFilterDiscountOnlyPolicyEnum) {
            return $discount_only_policy;
        }

        return CatalogFilterDiscountOnlyPolicyEnum::tryFrom((string)$discount_only_policy)
            ?? CatalogFilterDiscountOnlyPolicyEnum::ExcludeWithoutDiscount;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapProductsForResponse(
        LengthAwarePaginator $products,
        int                  $language_id,
        ?CatalogFilterSet    $filter_set,
        int                  $minimum_stock_quantity,
    ): array {
        $app_settings        = get_app_settings();
        $catalog_image_sizes = $app_settings->image_sizes?->firstWhere('name', 'search_product') ?? [];
        $locale_key          = config('localization.locale_parameter');

        return collect($products->items())
            ->map(function (Product $product) use ($language_id, $filter_set, $catalog_image_sizes, $minimum_stock_quantity, $locale_key): array {
                $rrc_price      = (float)$product->price;
                $discount_price = is_numeric($product->getAttribute('active_discount_price'))
                    ? (float)$product->getAttribute('active_discount_price')
                    : null;

                $effective_price = $this->price_source_resolver_service->resolveEffectivePrice(
                    rrc_price           : $rrc_price,
                    discount_price      : $discount_price,
                    price_source_mode   : $this->resolvePriceSourceMode($filter_set),
                    discount_only_policy: $this->resolveDiscountOnlyPolicy($filter_set),
                );

                $effective_price = $effective_price ?? $rrc_price;

                $formatted_effective_price = format_price(
                    $effective_price,
                    config('app.currency.default_currency_code'),
                    (float)config('app.currency.default_exchange_rate'),
                );

                $formatted_rrc_price = format_price(
                    $rrc_price,
                    config('app.currency.default_currency_code'),
                    (float)config('app.currency.default_exchange_rate'),
                );

                $product_slug = (string)optional($product->slugs->first())->slug;
                $product_url  = filled($product_slug)
                    ? localizedRoute('localized.catalog.product.show', ['slug' => $product_slug])
                    : '';

                return [
                    'id'          => (int)$product->id,
                    'name'        => (string)optional($product->productDescription->first())->name,
                    'sku'         => (string)$product->sku,
                    'quantity'    => (int)$product->quantity,
                    'is_in_stock' => (int)$product->quantity >= $minimum_stock_quantity,
                    'url'         => $product_url,
                    'image_data'  => [
                        'urls'   => multiple_convert_img_and_get_url(
                            $product->image,
                            (int)($catalog_image_sizes['width'] ?? 420),
                            (int)($catalog_image_sizes['height'] ?? 420),
                        ),
                        'width'  => (int)($catalog_image_sizes['width'] ?? 420),
                        'height' => (int)($catalog_image_sizes['height'] ?? 420),
                    ],
                    'price'       => [
                        'value'              => $effective_price,
                        'formatted'          => (string)$formatted_effective_price,
                        'rrc_value'          => $rrc_price,
                        'rrc_formatted'      => (string)$formatted_rrc_price,
                        'discount_value'     => $discount_price,
                        'discount_formatted' => $discount_price !== null
                            ? (string)format_price(
                                $discount_price,
                                config('app.currency.default_currency_code'),
                                (float)config('app.currency.default_exchange_rate'),
                            )
                            : null,
                    ],
                    $locale_key   => $language_id,
                ];
            })
            ->all();
    }

    /**
     * @param array<string, mixed> $validated_data
     *
     * @return array<string, mixed>
     */
    private function buildEmptyResponse(int $per_page, array $validated_data, string $sort_code): array
    {
        return [
            'products'          => [],
            'pagination'        => [
                'current_page' => 1,
                'last_page'    => 1,
                'per_page'     => $per_page,
                'total'        => 0,
                'from'         => null,
                'to'           => null,
            ],
            'applied_filters'   => [
                'sort'       => (string)Arr::get($validated_data, 'sort', ''),
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'stock'      => (array)Arr::get($validated_data, 'stock', []),
                'attributes' => (array)Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'  => $sort_code,
            'is_filter_enabled' => false,
        ];
    }

    private function resolveMinimumStockQuantity(?CatalogFilterSet $filter_set): int
    {
        if ($filter_set instanceof CatalogFilterSet && $filter_set->is_enabled) {
            return max(0, (int)$filter_set->min_stock_quantity);
        }

        return max(0, (int)config('app.products.minimum_stock_quantity', 1));
    }
}
