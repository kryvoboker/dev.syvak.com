<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CatalogFilter\CatalogFilterDiscountOnlyPolicyEnum;
use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Enums\CatalogFilter\CatalogFilterPriceSourceModeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterProductIndex;
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
        private PriceSourceResolverService $price_source_resolver_service,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function handle(array $params, ?string $locale = null): array
    {
        $validated_data      = Arr::get($params, 'validated_data', []);
        $category_slug       = (string) Arr::get($params, 'category_slug', '');
        $is_get_filters_data = (bool) Arr::get($params, 'is_get_filters_data', false);

        if (! is_array($validated_data)) {
            $validated_data = [];
        }

        $locale   = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (! $language instanceof Language || blank($category_slug)) {
            return $this->buildEmptyResponse(
                validated_data: $validated_data,
                sort_code: 'default',
                is_get_filters_data: $is_get_filters_data,
            );
        }

        $category = Category::findBySlug($category_slug, (int) $language->id);

        if (! $category instanceof Category) {
            return $this->buildEmptyResponse(
                validated_data: $validated_data,
                sort_code: 'default',
                is_get_filters_data: $is_get_filters_data,
            );
        }

        $filter_set = $this->resolveActiveCategoryFilterSet();

        $is_filter_mechanism_enabled = $filter_set instanceof CatalogFilterSet && $filter_set->is_enabled;
        $minimum_stock_quantity      = $this->resolveMinimumStockQuantity($filter_set);
        $filter_groups               = $is_filter_mechanism_enabled
            ? $this->resolveEnabledFilterGroups($filter_set)
            : collect();

        $requested_sort_value       = (string) Arr::get($validated_data, 'sort', '');
        $resolved_sort_code         = $this->resolveSortCodeFromRequestedValue($requested_sort_value);
        $effective_price_expression = $this->resolveEffectivePriceSqlExpression($filter_set);

        $products_query = $this->buildBaseProductsQuery(
            filter_set: $filter_set,
            category_id: (int) $category->id,
            language_id: (int) $language->id,
            minimum_stock_quantity: $minimum_stock_quantity,
        );

        if ($is_filter_mechanism_enabled) {
            $products_query = $this->applyAttributeFilters(
                query: $products_query,
                filter_set: $filter_set,
                filter_groups: $filter_groups,
                validated_data: $validated_data,
            );

            $products_query = $this->applyPriceRangeFilter(
                query: $products_query,
                filter_set: $filter_set,
                filter_groups: $filter_groups,
                validated_data: $validated_data,
                effective_price_expression: $effective_price_expression,
            );
        }

        $this->applySorting(
            query: $products_query,
            resolved_sort_code: $resolved_sort_code,
            effective_price_expression: $effective_price_expression,
        );

        $products = $products_query
            ->paginate($this->resolveCategoryProductsPerPage())
            ->withQueryString();

        return [
            'products' => $this->mapProductsForResponse(
                products: $products,
                language_id: (int) $language->id,
                filter_set: $filter_set,
                minimum_stock_quantity: $minimum_stock_quantity,
            ),
            'paginator'       => $products,
            'applied_filters' => [
                'sort'       => $requested_sort_value,
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'stock'      => (array) Arr::get($validated_data, 'stock', []),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'  => $resolved_sort_code,
            'is_filter_enabled' => $is_filter_mechanism_enabled,
            'filters_data'      => $is_get_filters_data
                ? $this->buildFiltersData(
                    filter_set: $filter_set,
                    filter_groups: $filter_groups,
                    language_id: (int) $language->id,
                    category_id: (int) $category->id,
                    minimum_stock_quantity: $minimum_stock_quantity,
                    validated_data: $validated_data,
                )
                : [],
        ];
    }

    /**
     * This method uses the same filter contract as `handle()` but returns only
     * matched products count for selected category/filter params.
     *
     * @param  array<string, mixed>  $validated_data
     *
     * @throws Throwable
     */
    public function count(array $validated_data, string $category_slug, ?string $locale = null): int
    {
        $response_data = $this->handle([
            'validated_data'      => $validated_data,
            'category_slug'       => $category_slug,
            'is_get_filters_data' => false,
        ], locale: $locale);

        /** @var LengthAwarePaginator|null $paginator */
        $paginator = Arr::get($response_data, 'paginator');

        return (int) $paginator?->total();
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

        if (! $filter_set instanceof CatalogFilterSet) {
            return null;
        }

        $filter_set->loadMissing([
            'indexMeta',
            'groups' => function ($query): void {
                $query
                    ->where('is_enabled', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'translations',
                        'values' => function ($values_query): void {
                            $values_query
                                ->where('is_enabled', true)
                                ->orderBy('sort_order')
                                ->orderBy('id')
                                ->with('translations');
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
                if ((string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Price->value) {
                    return (bool) $filter_set->is_price_filter_enabled;
                }

                if ((string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value) {
                    return (bool) $filter_set->is_attribute_filtering_enabled;
                }

                return true;
            })
            ->values();
    }

    private function buildBaseProductsQuery(
        ?CatalogFilterSet $filter_set,
        int $category_id,
        int $language_id,
        int $minimum_stock_quantity,
    ): Builder {
        $app_settings     = get_app_settings();
        $current_datetime = now(config('app.timezone'));

        $query = Product::query()
            ->select('products.*')
            ->selectRaw(config('database.prefix') . 'active_product_discount.price as active_discount_price')
            ->with([
                'slugs' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->leftJoin('product_discounts as active_product_discount', function (JoinClause $join) use ($app_settings, $current_datetime): void {
                $join
                    ->on('active_product_discount.product_id', '=', 'products.id')
                    ->where('active_product_discount.user_group_id', '=', (int) $app_settings->user_group_id)
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
     * @param  array<string, mixed>  $validated_data
     */
    private function applyAttributeFilters(
        Builder $query,
        CatalogFilterSet $filter_set,
        Collection $filter_groups,
        array $validated_data,
    ): Builder {
        if (! $filter_set->is_attribute_filtering_enabled) {
            return $query;
        }

        $attribute_filters = Arr::get($validated_data, 'attributes', []);

        if (! is_array($attribute_filters) || $attribute_filters === []) {
            return $query;
        }

        $attribute_groups_by_id = $filter_groups
            ->where(fn (CatalogFilterGroup $group): bool => (string) $group->getRawOriginal('source_type') === CatalogFilterGroupSourceTypeEnum::Attribute->value)
            ->keyBy(fn (CatalogFilterGroup $group): int => (int) $group->source_id);

        foreach ($attribute_filters as $attribute_id => $selected_codes) {
            $attribute_id = (int) $attribute_id;

            if ($attribute_id <= 0) {
                continue;
            }

            /** @var CatalogFilterGroup|null $attribute_group */
            $attribute_group = $attribute_groups_by_id->get($attribute_id);

            if (! $attribute_group instanceof CatalogFilterGroup) {
                continue;
            }

            $selected_codes = collect(is_array($selected_codes) ? $selected_codes : [$selected_codes])
                ->map(fn (mixed $code): string => (string) $code)
                ->filter(fn (string $code): bool => filled($code))
                ->unique()
                ->values();

            if ($selected_codes->isEmpty()) {
                continue;
            }

            $attribute_values = CatalogFilterValue::query()
                ->where('catalog_filter_group_id', (int) $attribute_group->id)
                ->where('is_enabled', true)
                ->whereIn('code', $selected_codes->all())
                ->pluck('value_string')
                ->map(fn (mixed $value): string => (string) $value)
                ->filter(fn (string $value): bool => filled($value))
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
     * @param  array<string, mixed>  $validated_data
     */
    private function applyPriceRangeFilter(
        Builder $query,
        CatalogFilterSet $filter_set,
        Collection $filter_groups,
        array $validated_data,
        string $effective_price_expression,
    ): Builder {
        if (! $filter_set->is_price_filter_enabled) {
            return $query;
        }

        /** @var CatalogFilterGroup|null $price_group */
        $price_group = $filter_groups
            ->first(fn (CatalogFilterGroup $group): bool => (string) $group->code === 'price');

        if (! $price_group instanceof CatalogFilterGroup) {
            return $query;
        }

        $price_from = Arr::get($validated_data, 'price_from');
        $price_to   = Arr::get($validated_data, 'price_to');

        if (is_numeric($price_from)) {
            $query->whereRaw($effective_price_expression . ' >= ?', [(float) $price_from]);
        }

        if (is_numeric($price_to)) {
            $query->whereRaw($effective_price_expression . ' <= ?', [(float) $price_to]);
        }

        return $query;
    }

    private function applySorting(Builder $query, string $resolved_sort_code, string $effective_price_expression): void
    {
        match ($resolved_sort_code) {
            'bestsellers' => $query
                ->orderByDesc('products.viewed')
                ->orderByDesc('products.id'),

            'price_asc' => $query
                ->orderByRaw($effective_price_expression . ' ASC')
                ->orderByDesc('products.id'),

            'price_desc' => $query
                ->orderByRaw($effective_price_expression . ' DESC')
                ->orderByDesc('products.id'),

            default => $query
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
        if (! $filter_set instanceof CatalogFilterSet) {
            return CatalogFilterPriceSourceModeEnum::Both;
        }

        $price_source_mode = $filter_set->price_source_mode;

        if ($price_source_mode instanceof CatalogFilterPriceSourceModeEnum) {
            return $price_source_mode;
        }

        return CatalogFilterPriceSourceModeEnum::tryFrom((string) $price_source_mode)
            ?? CatalogFilterPriceSourceModeEnum::Both;
    }

    private function resolveDiscountOnlyPolicy(?CatalogFilterSet $filter_set): CatalogFilterDiscountOnlyPolicyEnum
    {
        if (! $filter_set instanceof CatalogFilterSet) {
            return CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase;
        }

        $discount_only_policy = $filter_set->discount_only_policy;

        if ($discount_only_policy instanceof CatalogFilterDiscountOnlyPolicyEnum) {
            return $discount_only_policy;
        }

        return CatalogFilterDiscountOnlyPolicyEnum::tryFrom((string) $discount_only_policy)
            ?? CatalogFilterDiscountOnlyPolicyEnum::ExcludeWithoutDiscount;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapProductsForResponse(
        LengthAwarePaginator $products,
        int $language_id,
        ?CatalogFilterSet $filter_set,
        int $minimum_stock_quantity,
    ): array {
        $app_settings        = get_app_settings();
        $catalog_image_sizes = $app_settings->image_sizes?->firstWhere('name', 'search_product') ?? [];
        $locale_key          = config('localization.locale_parameter');

        return collect($products->items())
            ->map(function (Product $product) use ($language_id, $filter_set, $catalog_image_sizes, $minimum_stock_quantity, $locale_key): array {
                $rrc_price      = (float) $product->price;
                $discount_price = is_numeric($product->getAttribute('active_discount_price'))
                    ? (float) $product->getAttribute('active_discount_price')
                    : null;

                $effective_price = $this->price_source_resolver_service->resolveEffectivePrice(
                    rrc_price: $rrc_price,
                    discount_price: $discount_price,
                    price_source_mode: $this->resolvePriceSourceMode($filter_set),
                    discount_only_policy: $this->resolveDiscountOnlyPolicy($filter_set),
                );

                $effective_price = $effective_price ?? $rrc_price;

                $formatted_effective_price = format_price(
                    $effective_price,
                    config('app.currency.current_currency_code'),
                    (float) config('app.currency.current_exchange_rate'),
                );

                $formatted_rrc_price = format_price(
                    $rrc_price,
                    config('app.currency.current_currency_code'),
                    (float) config('app.currency.current_exchange_rate'),
                );

                $product_slug = (string) optional($product->slugs->first())->slug;
                $product_url  = filled($product_slug)
                    ? localizedRoute('localized.catalog.product.show', ['slug' => $product_slug])
                    : '';

                return [
                    'id'          => (int) $product->id,
                    'name'        => (string) optional($product->productDescription->first())->name,
                    'sku'         => (string) $product->sku,
                    'quantity'    => (int) $product->quantity,
                    'is_in_stock' => (int) $product->quantity >= $minimum_stock_quantity,
                    'url'         => $product_url,
                    'image_data'  => [
                        'urls' => multiple_convert_img_and_get_url(
                            $product->image,
                            (int) ($catalog_image_sizes['width'] ?? 420),
                            (int) ($catalog_image_sizes['height'] ?? 420),
                        ),
                        'width'  => (int) ($catalog_image_sizes['width'] ?? 420),
                        'height' => (int) ($catalog_image_sizes['height'] ?? 420),
                    ],
                    'price' => [
                        'value'              => $effective_price,
                        'formatted'          => (string) $formatted_effective_price,
                        'rrc_value'          => $rrc_price,
                        'rrc_formatted'      => (string) $formatted_rrc_price,
                        'discount_value'     => $discount_price,
                        'discount_formatted' => $discount_price !== null
                            ? (string) format_price(
                                $discount_price,
                                config('app.currency.current_currency_code'),
                                (float) config('app.currency.current_exchange_rate'),
                            )
                            : null,
                    ],
                    $locale_key => $language_id,
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, CatalogFilterGroup>  $filter_groups
     * @param  array<string, mixed>  $validated_data
     * @return array<string, array<string, mixed>>
     */
    private function buildFiltersData(
        ?CatalogFilterSet $filter_set,
        Collection $filter_groups,
        int $language_id,
        int $category_id,
        int $minimum_stock_quantity,
        array $validated_data,
    ): array {
        if (! $filter_set instanceof CatalogFilterSet || $filter_groups->isEmpty()) {
            return [];
        }

        $active_index_version = (int) optional($filter_set->indexMeta)->active_index_version;

        /** @var array<string, array<string, mixed>> $filters_data */
        $filters_data = [];

        foreach ($filter_groups as $group) {
            $group_key         = (string) $group->id;
            $group_source_type = (string) $group->getRawOriginal('source_type');

            $group_payload = [
                'group_id'    => $group_key,
                'group_code'  => (string) $group->code,
                'group_name'  => $this->resolveGroupLabel($group, $language_id),
                'source_type' => $group_source_type,
                'source_id'   => (int) $group->source_id,
                'get_key'     => (string) $group->get_key,
                'items'       => [],
            ];

            if ($group_source_type === CatalogFilterGroupSourceTypeEnum::Price->value) {
                $group_config = is_array($group->config) ? $group->config : [];

                $group_payload['range'] = [
                    'min'           => Arr::get($group_config, 'min_price'),
                    'max'           => Arr::get($group_config, 'max_price'),
                    'step'          => Arr::get($group_config, 'step'),
                    'selected_from' => Arr::get($validated_data, 'price_from'),
                    'selected_to'   => Arr::get($validated_data, 'price_to'),
                ];

                $filters_data[$group_key] = $group_payload;

                continue;
            }

            /** @var Collection<int, CatalogFilterValue> $group_values */
            $group_values = $group->values;

            foreach ($group_values as $value) {
                $value_key = (string) $value->id;

                $group_payload['items'][$value_key] = [
                    'id'             => (int) $value->id,
                    'code'           => (string) $value->code,
                    'name'           => $this->resolveValueLabel($value, $language_id),
                    'total_products' => $this->resolveValueProductsTotal(
                        filter_set_id: (int) $filter_set->id,
                        active_index_version: $active_index_version,
                        category_id: $category_id,
                        group_id: (int) $group->id,
                        value_id: (int) $value->id,
                        minimum_stock_quantity: $minimum_stock_quantity,
                        fallback_count: (int) $value->products_count_cached,
                    ),
                    'is_checked' => $this->resolveValueCheckedState(
                        group: $group,
                        value: $value,
                        validated_data: $validated_data,
                    ),
                ];
            }

            $filters_data[$group_key] = $group_payload;
        }

        return $filters_data;
    }

    private function resolveGroupLabel(CatalogFilterGroup $group, int $language_id): string
    {
        $translation = $group->translations
            ->firstWhere('language_id', $language_id)
            ?? $group->translations->first();

        $label = (string) ($translation ? $translation->label : '');

        if (filled($label)) {
            return $label;
        }

        return (string) $group->code;
    }

    private function resolveValueLabel(CatalogFilterValue $value, int $language_id): string
    {
        $translation = $value->translations
            ->firstWhere('language_id', $language_id)
            ?? $value->translations->first();

        $label = (string) ($translation ? $translation->label : '');

        if (filled($label)) {
            return $label;
        }

        if (filled((string) $value->value_string)) {
            return (string) $value->value_string;
        }

        return (string) $value->code;
    }

    /**
     * @param  array<string, mixed>  $validated_data
     */
    private function resolveValueCheckedState(CatalogFilterGroup $group, CatalogFilterValue $value, array $validated_data): bool
    {
        $source_type = (string) $group->getRawOriginal('source_type');
        $value_code  = (string) $value->code;

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Attribute->value) {
            $attribute_id   = (int) $group->source_id;
            $selected_codes = (array) Arr::get($validated_data, 'attributes.' . $attribute_id, []);

            return collect($selected_codes)
                ->map(fn (mixed $code): string => (string) $code)
                ->contains($value_code);
        }

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Stock->value) {
            $selected_stock_codes = (array) Arr::get($validated_data, 'stock', []);

            return collect($selected_stock_codes)
                ->map(fn (mixed $code): string => (string) $code)
                ->contains($value_code);
        }

        return false;
    }

    private function resolveValueProductsTotal(
        int $filter_set_id,
        int $active_index_version,
        int $category_id,
        int $group_id,
        int $value_id,
        int $minimum_stock_quantity,
        int $fallback_count,
    ): int {
        if ($active_index_version <= 0) {
            return max(0, $fallback_count);
        }

        $total_products = CatalogFilterProductIndex::query()
            ->where('catalog_filter_set_id', $filter_set_id)
            ->where('index_version', $active_index_version)
            ->where('category_id', $category_id)
            ->where('catalog_filter_group_id', $group_id)
            ->where('catalog_filter_value_id', $value_id)
            ->where('is_active_product', true)
            ->where('stock_quantity', '>=', $minimum_stock_quantity)
            ->distinct('product_id')
            ->count('product_id');

        return max(0, $total_products);
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    private function buildEmptyResponse(array $validated_data, string $sort_code, bool $is_get_filters_data): array
    {
        return [
            'products'        => [],
            'paginator'       => null,
            'applied_filters' => [
                'sort'       => (string) Arr::get($validated_data, 'sort', ''),
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'stock'      => (array) Arr::get($validated_data, 'stock', []),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'  => $sort_code,
            'is_filter_enabled' => false,
            'filters_data'      => $is_get_filters_data ? [] : [],
        ];
    }

    private function resolveMinimumStockQuantity(?CatalogFilterSet $filter_set): int
    {
        if ($filter_set instanceof CatalogFilterSet && $filter_set->is_enabled) {
            return max(0, (int) $filter_set->min_stock_quantity);
        }

        return max(0, (int) config('app.products.minimum_stock_quantity', 1));
    }
}
