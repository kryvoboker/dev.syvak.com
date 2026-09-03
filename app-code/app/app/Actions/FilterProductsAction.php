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
use App\Services\Catalogs\CatalogFilter\CatalogFilterBootstrapService;
use App\Services\Catalogs\CatalogFilter\PriceSourceResolverService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\Products\ProductsLimitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

readonly class FilterProductsAction
{
    public function __construct(
        private PageSettingsBootstrapService $page_settings_bootstrap_service,
        private PriceSourceResolverService $price_source_resolver_service,
        private CatalogFilterBootstrapService $catalog_filter_bootstrap_service,
    ) {
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws Throwable
     *
     * @return array<string, mixed>
     */
    public function handle(array $params, ?string $locale = null): array
    {
        $validated_data = Arr::get($params, 'validated_data', []);
        $category_slug = $this->toString(Arr::get($params, 'category_slug', ''));
        $is_get_filters_data = (bool) Arr::get($params, 'is_get_filters_data', false);
        $page_path = $this->toString(Arr::get($params, 'page_path', ''));

        if (! is_array($validated_data)) {
            $validated_data = [];
        }
        /** @var array<string, mixed> $validated_data */

        $query_context = $this->buildProductsQueryContext(
            validated_data: $validated_data,
            category_slug : $category_slug,
            locale        : $locale,
        );

        if ($query_context === null) {
            return $this->buildEmptyResponse(
                validated_data: $validated_data,
                sort_code     : 'default',
            );
        }

        $products = $query_context['query']
            ->paginate($this->resolveCategoryProductsPerPage())
            ->withQueryString();
        $products->setPath($this->resolvePaginatorPath($page_path));

        $available_keys_for_show_clear_btn = config('catalog-filter.available_keys_for_show_clear_btn', []);
        $available_keys_for_show_clear_btn = is_array($available_keys_for_show_clear_btn) ? $available_keys_for_show_clear_btn : [];
        $validated_keys = array_keys(array_filter($validated_data, fn (mixed $value): bool => filled($value)));
        $is_show_clear_filters_link = array_any(
            $validated_keys,
            fn (mixed $value): bool => in_array($value, $available_keys_for_show_clear_btn, true),
        );

        return [
            'products' => $this->mapProductsForResponse(
                products              : $products,
                language_id           : $query_context['language_id'],
                filter_set            : $query_context['filter_set'],
                minimum_stock_quantity: $query_context['minimum_stock_quantity'],
            ),
            'paginator' => $products,
            'applied_filters' => [
                'sort' => $query_context['requested_sort_value'],
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to' => Arr::get($validated_data, 'price_to'),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code' => $query_context['resolved_sort_code'],
            'selected_sort_value' => $query_context['requested_sort_value'],
            'is_filter_enabled' => $query_context['is_filter_mechanism_enabled'],
            'filters_data' => $is_get_filters_data
                ? $this->buildFiltersData(
                    filter_set            : $query_context['filter_set'],
                    filter_groups         : $query_context['filter_groups'],
                    language_id           : $query_context['language_id'],
                    category_id           : $query_context['category_id'],
                    minimum_stock_quantity: $query_context['minimum_stock_quantity'],
                    validated_data        : $validated_data,
                )
                : [],
            'is_show_clear_filters_link' => $is_show_clear_filters_link,
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
        $query_context = $this->buildProductsQueryContext(
            validated_data: $validated_data,
            category_slug : $category_slug,
            locale        : $locale,
        );

        return $query_context === null ? 0 : (int) $query_context['query']->count();
    }

    /**
     * @param array<string, mixed> $validated_data
     * @throws Throwable
     * @return array{
     *     query: Builder<Product>,
     *     category_id: int,
     *     language_id: int,
     *     filter_set: ?CatalogFilterSet,
     *     filter_groups: Collection<int, CatalogFilterGroup>,
     *     minimum_stock_quantity: int,
     *     requested_sort_value: string,
     *     resolved_sort_code: string,
     *     is_filter_mechanism_enabled: bool
     * }|null
     */
    private function buildProductsQueryContext(
        array $validated_data,
        string $category_slug,
        ?string $locale,
    ): ?array {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (! $language instanceof Language || blank($category_slug)) {
            return null;
        }

        $category = Category::findBySlug($category_slug, (int) $language->id);

        if (! $category instanceof Category) {
            return null;
        }

        $filter_set = $this->resolveActiveCategoryFilterSet();
        $is_filter_mechanism_enabled = $filter_set instanceof CatalogFilterSet && $filter_set->is_enabled;
        $minimum_stock_quantity = $this->resolveMinimumStockQuantity($filter_set);
        $filter_groups = $is_filter_mechanism_enabled
            ? $this->resolveEnabledFilterGroups($filter_set)
            : collect();
        $requested_sort_value = $this->normalizeRequestedSortValue(Arr::get($validated_data, 'sort', ''));
        $resolved_sort_code = $this->resolveSortCodeFromRequestedValue($requested_sort_value);
        $effective_price_expression = $this->resolveEffectivePriceSqlExpression($filter_set);
        $products_query = $this->buildBaseProductsQuery(
            filter_set            : $filter_set,
            category_id           : (int) $category->id,
            language_id           : (int) $language->id,
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

        return [
            'query' => $products_query,
            'category_id' => (int) $category->id,
            'language_id' => (int) $language->id,
            'filter_set' => $filter_set,
            'filter_groups' => $filter_groups,
            'minimum_stock_quantity' => $minimum_stock_quantity,
            'requested_sort_value' => $requested_sort_value,
            'resolved_sort_code' => $resolved_sort_code,
            'is_filter_mechanism_enabled' => $is_filter_mechanism_enabled,
        ];
    }

    /**
     * @throws Throwable
     */
    private function resolveCategoryProductsPerPage(): int
    {
        $category_page_setting = $this->page_settings_bootstrap_service->bootstrapCategoryPageSetting();
        $page_settings = get_page_settings($category_page_setting);

        return ProductsLimitService::getProductsCategoryLimit($page_settings);
    }

    private function resolveActiveCategoryFilterSet(): ?CatalogFilterSet
    {
        $filter_set = $this->catalog_filter_bootstrap_service->bootstrapDefaultCategorySet();

        if (
            ! $filter_set->is_enabled
            || (
                $this->toString($filter_set->getRawOriginal('context_type')) !== 'category'
                && ! in_array('category', (array) $filter_set->context_types, true)
            )
        ) {
            return null;
        }

        $filter_set->loadMissing([
            'indexMeta',
            'groups' => function (\Illuminate\Database\Eloquent\Relations\Relation $query): void {
                $query
                    ->where('is_enabled', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->with([
                        'translations',
                        'values' => function (\Illuminate\Database\Eloquent\Relations\Relation $values_query): void {
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
        $groups = $filter_set->groups;

        return $groups
            ->filter(function (CatalogFilterGroup $group) use ($filter_set): bool {
                return match ($this->toString($group->getRawOriginal('source_type'))) {
                    CatalogFilterGroupSourceTypeEnum::Price->value => (bool) $filter_set->is_price_filter_enabled,
                    CatalogFilterGroupSourceTypeEnum::Attribute->value => (bool) $filter_set->is_attribute_filtering_enabled,
                    default => false,
                };
            })
            ->values();
    }

    /** @return Builder<Product> */
    private function buildBaseProductsQuery(
        ?CatalogFilterSet $filter_set,
        int $category_id,
        int $language_id,
        int $minimum_stock_quantity,
    ): Builder {
        $app_settings = get_app_settings() ?? throw new \LogicException('Application settings are not initialized.');
        $current_datetime = now($this->toString(config('app.timezone')));
        /** @var literal-string $db_prefix */
        $db_prefix = $this->toString(config('database.prefix'));
        $query = Product::query()
            ->select('products.*')
            ->selectRaw($db_prefix . 'default_product_variant.id as default_variant_id_selected')
            ->selectRaw($db_prefix . 'default_product_variant.quantity as default_variant_quantity')
            ->selectRaw($db_prefix . 'default_product_variant.minimum as default_variant_minimum')
            ->selectRaw($db_prefix . 'default_product_variant.price as default_variant_price')
            ->selectRaw($db_prefix . 'default_product_variant.image as default_variant_image')
            ->selectRaw($db_prefix . 'active_product_discount.price as active_discount_price')
            ->with([
                'slugs' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'productDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'defaultVariant' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                    $query->with([
                        'slugs' => function (\Illuminate\Database\Eloquent\Relations\Relation $slug_query) use ($language_id): void {
                            $slug_query->where('language_id', $language_id);
                        },
                        'descriptions' => function (\Illuminate\Database\Eloquent\Relations\Relation $description_query) use ($language_id): void {
                            $description_query->where('language_id', $language_id);
                        },
                    ]);
                },
            ])
            ->leftJoin('product_variants as default_product_variant', function (JoinClause $join): void {
                $join
                    ->on('default_product_variant.product_id', '=', 'products.id')
                    ->where('default_product_variant.is_default', true);
            })
            ->leftJoin('product_variant_discounts as active_product_discount', function (JoinClause $join) use ($app_settings, $current_datetime): void {
                $join
                    ->on('active_product_discount.product_variant_id', '=', 'default_product_variant.id')
                    ->where('active_product_discount.user_group_id', '=', (int) $app_settings->user_group_id)
                    ->where('active_product_discount.date_start', '<=', $current_datetime)
                    ->where('active_product_discount.date_end', '>=', $current_datetime);
            })
            ->where('products.is_active', true)
            ->where('default_product_variant.is_active', true)
            ->where('default_product_variant.quantity', '>=', $minimum_stock_quantity)
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
     * @param Collection<int, CatalogFilterGroup> $filter_groups
     * @param Builder<Product> $query
     * @psalm-param Collection<int, CatalogFilterGroup> $filter_groups
     * @return Builder<Product>
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
            ->where(fn (CatalogFilterGroup $group): bool => $this->toString($group->getRawOriginal('source_type')) === CatalogFilterGroupSourceTypeEnum::Attribute->value)
            ->keyBy(fn (CatalogFilterGroup $group): int => $this->toInt($group->source_id));

        foreach ($attribute_filters as $attribute_id => $selected_codes) {
            $attribute_id = $this->toInt($attribute_id);

            if ($attribute_id <= 0) {
                continue;
            }

            $attribute_group = $attribute_groups_by_id->get($attribute_id);

            if (! $attribute_group instanceof CatalogFilterGroup) {
                continue;
            }

            $selected_codes = collect(is_array($selected_codes) ? $selected_codes : [$selected_codes])
                ->map(fn (mixed $code): string => $this->toString($code))
                ->filter(fn (string $code): bool => filled($code))
                ->unique()
                ->values();

            if ($selected_codes->isEmpty()) {
                continue;
            }

            /**
             * Filter codes are stable URL values, while variant attributes are localized.
             * Build candidate value set from canonical filter value + all translations
             * to avoid locale mismatch that causes false-zero results.
             */
            $attribute_values = $attribute_group->values
                ->toBase()
                ->filter(fn (CatalogFilterValue $value): bool => $selected_codes->contains((string) $value->code))
                ->flatMap(function (CatalogFilterValue $value): array {
                    $value_candidates = [(string) $value->value_string];
                    $translation_labels = $value->translations
                        ->pluck('label')
                        ->map(fn (mixed $label): string => $this->toString($label))
                        ->all();
                    /** @var array<int, string> $translation_labels */

                    return [
                        ...$value_candidates,
                        ...$translation_labels,
                    ];
                })
                ->map(fn (mixed $value): string => $this->normalizeAttributeValue($this->toString($value)))
                ->filter(fn (string $value): bool => filled($value))
                ->unique()
                ->values();

            if ($attribute_values->isEmpty()) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $query->whereHas('variants', function ($variant_query) use ($attribute_id, $attribute_values): void {
                $variant_query
                    ->where('is_active', true)
                    ->whereHas('attributeValues', function ($attribute_query) use ($attribute_id, $attribute_values): void {
                        $attribute_query
                            ->where('attribute_id', $attribute_id)
                            ->whereIn('value_string', $attribute_values->all());
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @param Collection<int, CatalogFilterGroup> $filter_groups
     * @param Builder<Product> $query
     * @return Builder<Product>
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

        $price_group = $filter_groups
            ->first(fn (CatalogFilterGroup $group): bool => (string) $group->code === CatalogFilterGroupSourceTypeEnum::Price->value);

        if (! $price_group instanceof CatalogFilterGroup) {
            return $query;
        }

        $price_from = Arr::get($validated_data, 'price_from');
        $price_to = Arr::get($validated_data, 'price_to');

        if (is_numeric($price_from)) {
            // @phpstan-ignore argument.type (The expression is built only from trusted table names.)
            $query->whereRaw(new Expression($effective_price_expression . ' >= ?'), [(float) $price_from]);
        }

        if (is_numeric($price_to)) {
            // @phpstan-ignore argument.type (The expression is built only from trusted table names.)
            $query->whereRaw(new Expression($effective_price_expression . ' <= ?'), [(float) $price_to]);
        }

        return $query;
    }

    /** @param Builder<Product> $query */
    private function applySorting(Builder $query, string $resolved_sort_code, string $effective_price_expression): void
    {
        /**
         * Sorting uses canonical sort codes from page settings.
         * Unknown values are normalized before this point and resolved to `default`.
         */
        match ($resolved_sort_code) {
            'bestsellers' => $query
                ->orderByDesc('products.viewed')
                ->orderByDesc('products.id'),

            'price-asc' => $query
                // @phpstan-ignore argument.type (The expression is built only from trusted table names.)
                ->orderBy(new Expression($effective_price_expression . ' ASC'))
                ->orderByDesc('products.id'),

            'price-desc' => $query
                // @phpstan-ignore argument.type (The expression is built only from trusted table names.)
                ->orderBy(new Expression($effective_price_expression . ' DESC'))
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

    /** @return non-falsy-string */
    private function resolveEffectivePriceSqlExpression(?CatalogFilterSet $filter_set): string
    {
        $price_source_mode = $this->resolvePriceSourceMode($filter_set);
        $discount_only_policy = $this->resolveDiscountOnlyPolicy($filter_set);
        /** @var non-falsy-string $db_prefix */
        $db_prefix = $this->toString(config('database.prefix'));

        /** @var non-falsy-string $expression */
        $expression = match (true) {
            $price_source_mode === CatalogFilterPriceSourceModeEnum::RrcOnly => $db_prefix . 'default_product_variant.price',
            $price_source_mode === CatalogFilterPriceSourceModeEnum::Both => "COALESCE({$db_prefix}active_product_discount.price, {$db_prefix}default_product_variant.price)",
            $discount_only_policy === CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase => "COALESCE({$db_prefix}active_product_discount.price, {$db_prefix}default_product_variant.price)",
            default => $db_prefix . 'active_product_discount.price',
        };

        return $expression;
    }

    private function resolvePriceSourceMode(?CatalogFilterSet $filter_set): CatalogFilterPriceSourceModeEnum
    {
        if (! $filter_set instanceof CatalogFilterSet) {
            return CatalogFilterPriceSourceModeEnum::Both;
        }

        $price_source_mode = $filter_set->price_source_mode;

        return $price_source_mode;
    }

    private function resolveDiscountOnlyPolicy(?CatalogFilterSet $filter_set): CatalogFilterDiscountOnlyPolicyEnum
    {
        if (! $filter_set instanceof CatalogFilterSet) {
            return CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase;
        }

        $discount_only_policy = $filter_set->discount_only_policy;

        return $discount_only_policy;
    }

    /**
     * @param LengthAwarePaginator<int, Product> $products
     * @throws Throwable
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapProductsForResponse(
        LengthAwarePaginator $products,
        int $language_id,
        ?CatalogFilterSet $filter_set,
        int $minimum_stock_quantity,
    ): array {
        $catalog_image_sizes = $this->page_settings_bootstrap_service->getCategoryProductImageSize();
        $locale_key = $this->toString(config('localization.locale_parameter'), 'locale');

        $product_items = $products->items();

        return collect($product_items)
            ->map(function (Product $product) use ($language_id, $filter_set, $catalog_image_sizes, $minimum_stock_quantity, $locale_key): array {
                $variant_price = $product->getAttribute('default_variant_price');
                $variant_stock = $product->getAttribute('default_variant_quantity');
                $variant_image = $product->getAttribute('default_variant_image');
                $rrc_price = is_numeric($variant_price) ? (float) $variant_price : (float) $product->price;
                $discount_price = is_numeric($product->getAttribute('active_discount_price'))
                    ? (float) $product->getAttribute('active_discount_price')
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
                    $this->toString(config('app.currency.current_currency_code')),
                    $this->toFloat(config('app.currency.current_exchange_rate')),
                );

                $formatted_rrc_price = format_price(
                    $rrc_price,
                    $this->toString(config('app.currency.current_currency_code')),
                    $this->toFloat(config('app.currency.current_exchange_rate')),
                );

                $product_slug = $this->toString($product->slugs->first()?->slug);
                $default_variant = $product->defaultVariant;
                $variant_slug = $default_variant instanceof \App\Models\Catalogs\Products\ProductVariant
                    ? $this->toString($default_variant->slugs->first()?->slug)
                    : '';
                $variant_description = $default_variant instanceof \App\Models\Catalogs\Products\ProductVariant
                    ? $default_variant->descriptions->first()
                    : null;
                $fallback_description = $product->productDescription->first();

                if (filled($product_slug) && filled($variant_slug)) {
                    $product_url = localized_route('localized.catalog.product.variant.show', [
                        'slug' => $product_slug,
                        'variant_slug' => $variant_slug,
                    ]);
                } else {
                    $product_url = filled($product_slug)
                        ? localized_route('localized.catalog.product.show', ['slug' => $product_slug])
                        : '';
                }

                return [
                    'id' => $this->toInt($product->id),
                    'variant_id' => $this->toInt($product->getAttribute('default_variant_id_selected')),
                    'name' => $this->toString($variant_description->name ?? $fallback_description->name ?? ''),
                    'sku' => $this->toString($product->sku),
                    'quantity' => $this->toInt($variant_stock),
                    'is_in_stock' => $this->toInt($variant_stock) >= $minimum_stock_quantity,
                    'url' => $product_url,
                    'image_data' => [
                        'urls' => multiple_convert_img_and_get_url(
                            $this->toString($variant_image),
                            $this->toInt($catalog_image_sizes['width']),
                            $this->toInt($catalog_image_sizes['height']),
                        ),
                        'width' => $this->toInt($catalog_image_sizes['width']),
                        'height' => $this->toInt($catalog_image_sizes['height']),
                    ],
                    'price' => [
                        'value' => $effective_price,
                        'formatted' => (string) $formatted_effective_price,
                        'rrc_value' => $rrc_price,
                        'rrc_formatted' => (string) $formatted_rrc_price,
                        'discount_value' => $discount_price,
                        'discount_formatted' => $discount_price !== null
                            ? (string) format_price(
                                $discount_price,
                                $this->toString(config('app.currency.current_currency_code')),
                                $this->toFloat(config('app.currency.current_exchange_rate')),
                            )
                            : null,
                    ],
                    $locale_key => $language_id,
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, CatalogFilterGroup> $filter_groups
     * @param array<string, mixed> $validated_data
     * @psalm-param Collection<int, CatalogFilterGroup> $filter_groups
     * @return array<int|string, array<string, mixed>>
     * @psalm-suppress InvalidTemplateParam
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

        $active_index_version = $this->toInt($filter_set->indexMeta?->active_index_version);
        $dynamic_price_max = $this->resolveDynamicPriceRangeMax(
            filter_set            : $filter_set,
            filter_groups         : $filter_groups,
            category_id           : $category_id,
            language_id           : $language_id,
            minimum_stock_quantity: $minimum_stock_quantity,
            validated_data        : $validated_data,
        );

        /** @var array<int|string, array<string, mixed>> $filters_data */
        $filters_data = [];

        foreach ($filter_groups as $group) {
            $group_key = $this->toString($group->id);
            $group_source_type = $this->toString($group->getRawOriginal('source_type'));

            $group_payload = [
                'group_id' => $group_key,
                'group_code' => $this->toString($group->code),
                'group_name' => $this->resolveGroupLabel($group, $language_id),
                'source_type' => $group_source_type,
                'source_id' => $this->toInt($group->source_id),
                'get_key' => $this->toString($group->get_key),
                'get_value' => $this->toString(Arr::get((array) ($group->config ?? []), 'get.value', '')),
                'get_extra' => (array) Arr::get((array) ($group->config ?? []), 'get.extra', []),
                'items' => [],
            ];

            if ($group_source_type === CatalogFilterGroupSourceTypeEnum::Price->value) {
                $group_config = (array) $group->config;
                $selected_from = Arr::get($validated_data, 'price_from');
                $selected_to = Arr::get($validated_data, 'price_to');
                $price_from_key = $this->toString(Arr::get($group_config, 'get.extra.from_key', 'price_from'));
                $price_to_key = $this->toString(Arr::get($group_config, 'get.extra.to_key', 'price_to'));

                if (blank($price_from_key)) {
                    $price_from_key = 'price_from';
                }

                if (blank($price_to_key)) {
                    $price_to_key = 'price_to';
                }

                $group_payload['range'] = [
                    'min' => Arr::get($group_config, 'min_price'),
                    'max' => is_numeric($dynamic_price_max)
                        ? $dynamic_price_max
                        : Arr::get($group_config, 'max_price'),
                    'step' => Arr::get($group_config, 'step'),
                    'selected_from' => $selected_from,
                    'selected_to' => $selected_to,
                    'cancel_link' => $this->buildCancelLinkForPriceRange(
                        price_from_get_key: $price_from_key,
                        price_to_get_key  : $price_to_key,
                        selected_from     : $selected_from,
                        selected_to       : $selected_to,
                    ),
                ];

                $filters_data[$group_key] = $group_payload;

                continue;
            }

            $group_values = $group->values;

            foreach ($group_values as $value) {
                $value_key = (string) $value->id;
                $is_checked = $this->resolveValueCheckedState(
                    group         : $group,
                    value         : $value,
                    validated_data: $validated_data,
                );

                $group_payload['items'][$value_key] = [
                    'id' => (int) $value->id,
                    'code' => (string) $value->code,
                    'name' => $this->resolveValueLabel($value, $language_id),
                    'total_products' => $this->resolveValueProductsTotal(
                        filter_set_id         : (int) $filter_set->id,
                        active_index_version  : $active_index_version,
                        category_id           : $category_id,
                        group_id              : (int) $group->id,
                        value_id              : (int) $value->id,
                        minimum_stock_quantity: $minimum_stock_quantity,
                        fallback_count        : (int) $value->products_count_cached,
                        group                 : $group,
                        value                 : $value,
                        language_id           : $language_id,
                        filter_set            : $filter_set,
                    ),
                    'is_checked' => $is_checked,
                    'cancel_link' => $is_checked
                        ? $this->buildCancelLinkForCheckedValue($group, $value)
                        : null,
                ];
            }

            $filters_data[$group_key] = $group_payload;
        }

        return $filters_data;
    }

    /**
     * Resolve dynamic max price from currently eligible products:
     * category + stock threshold + selected attribute options.
     * Price bounds from request are intentionally not applied here, because this
     * value is used as a price-filter upper bound candidate.
     * @param Collection<int, CatalogFilterGroup> $filter_groups
     * @param array<string, mixed> $validated_data
     */
    private function resolveDynamicPriceRangeMax(
        CatalogFilterSet $filter_set,
        Collection $filter_groups,
        int $category_id,
        int $language_id,
        int $minimum_stock_quantity,
        array $validated_data,
    ): ?float {
        $query = $this->buildBaseProductsQuery(
            filter_set            : $filter_set,
            category_id           : $category_id,
            language_id           : $language_id,
            minimum_stock_quantity: $minimum_stock_quantity,
        );

        $query = $this->applyAttributeFilters(
            query         : $query,
            filter_set    : $filter_set,
            filter_groups : $filter_groups,
            validated_data: $validated_data,
        );

        $effective_price_expression = $this->resolveEffectivePriceSqlExpression($filter_set);
        $query_base = $query->toBase();
        $query_base->columns = [];

        $max_price = $query_base
            // @phpstan-ignore argument.type (The expression is built only from trusted table names.)
            ->selectRaw('MAX(' . $effective_price_expression . ') as max_effective_price')
            ->value('max_effective_price');

        return is_numeric($max_price) ? (float) $max_price : null;
    }

    private function buildCancelLinkForCheckedValue(CatalogFilterGroup $group, CatalogFilterValue $value): ?string
    {
        $get_key = $this->toString($group->get_key);

        if (blank($get_key) || ! app()->bound('request')) {
            return null;
        }

        $request = request();
        $query_parameters = (array) $request->query();
        /** @var array<string, mixed> $query_parameters */
        $query_value = $this->extractQueryValueByGetKey($query_parameters, $get_key);

        if ($query_value === null) {
            return $request->fullUrl();
        }

        $remaining_values = collect($this->normalizeFilterQueryValues($query_value))
            ->reject(fn (string $candidate_value): bool => $candidate_value === (string) $value->code)
            ->values()
            ->all();

        $next_value = null;

        if ($remaining_values !== []) {
            $next_value = is_array($query_value)
                ? $remaining_values
                : implode(',', $remaining_values);
        }

        $next_query_parameters = $this->replaceQueryValueByGetKey(
            query_parameters: $query_parameters,
            get_key         : $get_key,
            next_value      : $next_value,
        );
        Arr::forget($next_query_parameters, 'page');

        $next_query_string = Arr::query($next_query_parameters);

        return filled($next_query_string)
            ? $request->url() . '?' . $next_query_string
            : $request->url();
    }

    private function buildCancelLinkForPriceRange(
        string $price_from_get_key,
        string $price_to_get_key,
        mixed $selected_from,
        mixed $selected_to,
    ): ?string {
        if (($selected_from === null && $selected_to === null) || ! app()->bound('request')) {
            return null;
        }

        $request = request();
        $next_query_parameters = (array) $request->query();
        /** @var array<string, mixed> $next_query_parameters */
        /** @var array<string, mixed> $typed_query_parameters */
        $typed_query_parameters = $next_query_parameters;
        $next_query_parameters = $this->replaceQueryValueByGetKey(
            query_parameters: $typed_query_parameters,
            get_key         : $price_from_get_key,
            next_value      : null,
        );
        $next_query_parameters = $this->replaceQueryValueByGetKey(
            query_parameters: $next_query_parameters,
            get_key         : $price_to_get_key,
            next_value      : null,
        );
        Arr::forget($next_query_parameters, 'page');

        $next_query_string = Arr::query($next_query_parameters);

        return filled($next_query_string)
            ? $request->url() . '?' . $next_query_string
            : $request->url();
    }

    /** @param array<string, mixed> $query_parameters */
    private function extractQueryValueByGetKey(array $query_parameters, string $get_key): mixed
    {
        if (Str::contains($get_key, '[') && Str::endsWith($get_key, ']')) {
            $normalized_get_key = str_replace(['[', ']'], ['.', ''], $get_key);

            return data_get($query_parameters, $normalized_get_key);
        }

        return Arr::get($query_parameters, $get_key);
    }

    /**
     * @param  array<string, mixed>  $query_parameters
     * @return array<string, mixed>
     */
    private function replaceQueryValueByGetKey(array $query_parameters, string $get_key, mixed $next_value): array
    {
        if (Str::contains($get_key, '[') && Str::endsWith($get_key, ']')) {
            $normalized_get_key = str_replace(['[', ']'], ['.', ''], $get_key);

            if ($next_value === null) {
                Arr::forget($query_parameters, $normalized_get_key);
                $root_key = Str::before($normalized_get_key, '.');

                if (Arr::get($query_parameters, $root_key) === []) {
                    Arr::forget($query_parameters, $root_key);
                }

                /** @var array<string, mixed> $query_parameters */
                return $query_parameters;
            }

            Arr::set($query_parameters, $normalized_get_key, $next_value);

            /** @var array<string, mixed> $query_parameters */
            return $query_parameters;
        }

        if ($next_value === null) {
            Arr::forget($query_parameters, $get_key);

            /** @var array<string, mixed> $query_parameters */
            return $query_parameters;
        }

        Arr::set($query_parameters, $get_key, $next_value);

        /** @var array<string, mixed> $query_parameters */
        return $query_parameters;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeFilterQueryValues(mixed $raw_value): array
    {
        if (! is_array($raw_value)) {
            $raw_value = [$raw_value];
        }

        return collect($raw_value)
            ->flatten(1)
            ->flatMap(function (mixed $item): array {
                $string_value = $this->toString($item);

                if (blank($string_value)) {
                    return [];
                }

                return collect(explode(',', $string_value))
                    ->map(fn (string $part): string => trim($part))
                    ->filter(fn (string $part): bool => filled($part))
                    ->values()
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeAttributeValue(string $value): string
    {
        return trim($value);
    }

    private function resolveGroupLabel(CatalogFilterGroup $group, int $language_id): string
    {
        $translation = $group->translations
            ->firstWhere('language_id', $language_id)
            ?? $group->translations->first();

        $label = $this->toString($translation?->label);

        if (filled($label)) {
            return $label;
        }

        return $this->toString($group->code);
    }

    private function resolveValueLabel(CatalogFilterValue $value, int $language_id): string
    {
        $translation = $value->translations
            ->firstWhere('language_id', $language_id)
            ?? $value->translations->first();

        $label = $this->toString($translation?->label);

        if (filled($label)) {
            return $label;
        }

        if (filled($this->toString($value->value_string))) {
            return $this->toString($value->value_string);
        }

        return $this->toString($value->code);
    }

    /**
     * @param  array<string, mixed>  $validated_data
     */
    private function resolveValueCheckedState(CatalogFilterGroup $group, CatalogFilterValue $value, array $validated_data): bool
    {
        $source_type = $this->toString($group->getRawOriginal('source_type'));
        $value_code = $this->toString($value->code);

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Attribute->value) {
            $attribute_id = (int) $group->source_id;
            $selected_codes = $this->normalizeFilterQueryValues(Arr::get($validated_data, 'attributes.' . $attribute_id, []));

            return in_array($value_code, $selected_codes, true);
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
        CatalogFilterGroup $group,
        CatalogFilterValue $value,
        int $language_id,
        CatalogFilterSet $filter_set,
    ): int {
        if ($active_index_version <= 0) {
            return $this->resolveLiveValueProductsTotal(
                category_id           : $category_id,
                minimum_stock_quantity: $minimum_stock_quantity,
                group                 : $group,
                value                 : $value,
                language_id           : $language_id,
                filter_set            : $filter_set,
                fallback_count        : $fallback_count,
            );
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

        if ($total_products > 0) {
            return $total_products;
        }

        return $this->resolveLiveValueProductsTotal(
            category_id           : $category_id,
            minimum_stock_quantity: $minimum_stock_quantity,
            group                 : $group,
            value                 : $value,
            language_id           : $language_id,
            filter_set            : $filter_set,
            fallback_count        : $fallback_count,
        );
    }

    private function resolveLiveValueProductsTotal(
        int $category_id,
        int $minimum_stock_quantity,
        CatalogFilterGroup $group,
        CatalogFilterValue $value,
        int $language_id,
        CatalogFilterSet $filter_set,
        int $fallback_count,
    ): int {
        $attribute_id = $this->toInt($group->source_id);
        $value_candidates = collect([$this->toString($value->value_string)])
            ->merge($value->translations->pluck('label'))
            ->map(fn (mixed $candidate): string => $this->normalizeAttributeValue($this->toString($candidate)))
            ->filter(fn (string $candidate): bool => filled($candidate))
            ->unique()
            ->values();

        if ($attribute_id <= 0 || $value_candidates->isEmpty()) {
            return max(0, $fallback_count);
        }

        return max(0, (int) $this->buildBaseProductsQuery(
            filter_set: $filter_set,
            category_id: $category_id,
            language_id: $language_id,
            minimum_stock_quantity: $minimum_stock_quantity,
        )->whereHas('variants', function ($variant_query) use ($attribute_id, $value_candidates): void {
            $variant_query
                ->where('is_active', true)
                ->whereHas('attributeValues', function ($attribute_query) use ($attribute_id, $value_candidates): void {
                    $attribute_query
                        ->where('attribute_id', $attribute_id)
                        ->whereIn('value_string', $value_candidates->all());
                });
        })->count());
    }

    /**
     * @param  array<string, mixed>  $validated_data
     * @return array<string, mixed>
     */
    private function buildEmptyResponse(array $validated_data, string $sort_code): array
    {
        $requested_sort_value = $this->normalizeRequestedSortValue(Arr::get($validated_data, 'sort', ''));

        return [
            'products' => [],
            'paginator' => null,
            'applied_filters' => [
                'sort' => $requested_sort_value,
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to' => Arr::get($validated_data, 'price_to'),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code' => $sort_code,
            'selected_sort_value' => $requested_sort_value,
            'is_filter_enabled' => false,
            'filters_data' => [],
        ];
    }

    /**
     * @throws Throwable
     */
    private function resolveMinimumStockQuantity(?CatalogFilterSet $filter_set): int
    {
        if ($filter_set instanceof CatalogFilterSet && $filter_set->is_enabled) {
            return max(0, (int) $filter_set->min_stock_quantity);
        }

        return max(0, $this->page_settings_bootstrap_service->getProductMinimumStockQuantity());
    }

    private function normalizeRequestedSortValue(mixed $sort_value): string
    {
        return Str::lower($this->toString($sort_value));
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function toString(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    private function resolvePaginatorPath(string $page_path): string
    {
        if (filled($page_path)) {
            return $page_path;
        }

        if (app()->bound('request')) {
            return request()->url();
        }

        return '/';
    }
}
