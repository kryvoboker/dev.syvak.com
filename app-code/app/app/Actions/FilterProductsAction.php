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
use Illuminate\Support\Str;
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
        $page_path           = (string) Arr::get($params, 'page_path', '');

        if (! is_array($validated_data)) {
            $validated_data = [];
        }

        $locale   = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (! $language instanceof Language || blank($category_slug)) {
            return $this->buildEmptyResponse(
                validated_data: $validated_data,
                sort_code     : 'default',
            );
        }

        $category = Category::findBySlug($category_slug, (int) $language->id);

        if (! $category instanceof Category) {
            return $this->buildEmptyResponse(
                validated_data: $validated_data,
                sort_code     : 'default',
            );
        }

        $filter_set                  = $this->resolveActiveCategoryFilterSet();
        $is_filter_mechanism_enabled = $filter_set instanceof CatalogFilterSet && $filter_set->is_enabled;
        $minimum_stock_quantity      = $this->resolveMinimumStockQuantity($filter_set);
        $filter_groups               = $is_filter_mechanism_enabled
            ? $this->resolveEnabledFilterGroups($filter_set)
            : collect();

        $requested_sort_value       = $this->normalizeRequestedSortValue(Arr::get($validated_data, 'sort', ''));
        $resolved_sort_code         = $this->resolveSortCodeFromRequestedValue($requested_sort_value);
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

        $products = $products_query
            ->paginate($this->resolveCategoryProductsPerPage())
            ->withQueryString();
        $products->setPath($this->resolvePaginatorPath($page_path));

        $available_keys_for_show_clear_btn = config('catalog-filter.available_keys_for_show_clear_btn', []);
        $validated_keys                    = array_keys(array_filter($validated_data, fn (mixed $value): bool => filled($value)));
        $is_show_clear_filters_link        = array_any(
            $validated_keys,
            fn (mixed $value): bool => in_array($value, $available_keys_for_show_clear_btn, true),
        );

        return [
            'products' => $this->mapProductsForResponse(
                products              : $products,
                language_id           : (int) $language->id,
                filter_set            : $filter_set,
                minimum_stock_quantity: $minimum_stock_quantity,
            ),
            'paginator'       => $products,
            'applied_filters' => [
                'sort'       => $requested_sort_value,
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'    => $resolved_sort_code,
            'selected_sort_value' => $requested_sort_value,
            'is_filter_enabled'   => $is_filter_mechanism_enabled,
            'filters_data'        => $is_get_filters_data
                ? $this->buildFiltersData(
                    filter_set            : $filter_set,
                    filter_groups         : $filter_groups,
                    language_id           : (int) $language->id,
                    category_id           : (int) $category->id,
                    minimum_stock_quantity: $minimum_stock_quantity,
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
                return match ((string) $group->getRawOriginal('source_type')) {
                    CatalogFilterGroupSourceTypeEnum::Price->value     => (bool) $filter_set->is_price_filter_enabled,
                    CatalogFilterGroupSourceTypeEnum::Attribute->value => (bool) $filter_set->is_attribute_filtering_enabled,
                    default                                            => false,
                };
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
        $db_prefix        = config('database.prefix');
        $query            = Product::query()
            ->select('products.*')
            ->selectRaw($db_prefix . 'default_product_variant.id as default_variant_id_selected')
            ->selectRaw($db_prefix . 'default_product_variant.quantity as default_variant_quantity')
            ->selectRaw($db_prefix . 'default_product_variant.minimum as default_variant_minimum')
            ->selectRaw($db_prefix . 'default_product_variant.price as default_variant_price')
            ->selectRaw($db_prefix . 'default_product_variant.image as default_variant_image')
            ->selectRaw($db_prefix . 'active_product_discount.price as active_discount_price')
            ->with([
                'slugs' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'defaultVariant' => function ($query) use ($language_id): void {
                    $query->with([
                        'slugs' => function ($slug_query) use ($language_id): void {
                            $slug_query->where('language_id', $language_id);
                        },
                        'descriptions' => function ($description_query) use ($language_id): void {
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

            /**
             * Filter codes are stable URL values, while variant attributes are localized.
             * Build candidate value set from canonical filter value + all translations
             * to avoid locale mismatch that causes false-zero results.
             */
            $attribute_values = $attribute_group->values
                ->filter(fn (CatalogFilterValue $value): bool => $selected_codes->contains((string) $value->code))
                ->flatMap(function (CatalogFilterValue $value): array {
                    $value_candidates   = [(string) $value->value_string];
                    $translation_labels = $value->translations
                        ->pluck('label')
                        ->map(fn (mixed $label): string => (string) $label)
                        ->all();

                    return [...$value_candidates, ...$translation_labels];
                })
                ->map(fn (mixed $value): string => $this->normalizeAttributeValue((string) $value))
                ->filter(fn (string $value): bool => filled($value))
                ->unique()
                ->values();

            if ($attribute_values->isEmpty()) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $query->whereHas('defaultVariant.attributeValues', function ($attribute_query) use ($attribute_id, $attribute_values): void {
                $attribute_query
                    ->where('attribute_id', $attribute_id)
                    ->whereIn('value_string', $attribute_values->all());
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
            ->first(fn (CatalogFilterGroup $group): bool => (string) $group->code === CatalogFilterGroupSourceTypeEnum::Price->value);

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
        /**
         * Sorting uses canonical sort codes from page settings.
         * Unknown values are normalized before this point and resolved to `default`.
         */
        match ($resolved_sort_code) {
            'bestsellers' => $query
                ->orderByDesc('products.viewed')
                ->orderByDesc('products.id'),

            'price-asc' => $query
                ->orderByRaw($effective_price_expression . ' ASC')
                ->orderByDesc('products.id'),

            'price-desc' => $query
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
        $db_prefix            = config('database.prefix');

        return match (true) {
            $price_source_mode === CatalogFilterPriceSourceModeEnum::RrcOnly              => $db_prefix . 'default_product_variant.price',
            $price_source_mode === CatalogFilterPriceSourceModeEnum::Both                 => "COALESCE({$db_prefix}active_product_discount.price, {$db_prefix}default_product_variant.price)",
            $discount_only_policy === CatalogFilterDiscountOnlyPolicyEnum::FallbackToBase => "COALESCE({$db_prefix}active_product_discount.price, {$db_prefix}default_product_variant.price)",
            default                                                                       => $db_prefix . 'active_product_discount.price',
        };
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
        $locale_key          = config('localization.locale_parameter');

        return collect($products->items())
            ->map(function (Product $product) use ($language_id, $filter_set, $catalog_image_sizes, $minimum_stock_quantity, $locale_key): array {
                $variant_price  = $product->getAttribute('default_variant_price');
                $variant_stock  = $product->getAttribute('default_variant_quantity');
                $variant_image  = $product->getAttribute('default_variant_image');
                $rrc_price      = is_numeric($variant_price) ? (float) $variant_price : (float) $product->price;
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
                    config('app.currency.current_currency_code'),
                    (float) config('app.currency.current_exchange_rate'),
                );

                $formatted_rrc_price = format_price(
                    $rrc_price,
                    config('app.currency.current_currency_code'),
                    (float) config('app.currency.current_exchange_rate'),
                );

                $product_slug         = (string) optional($product->slugs->first())->slug;
                $variant_slug         = (string) optional(optional($product->defaultVariant)->slugs->first())->slug;
                $variant_description  = optional($product->defaultVariant)->descriptions->first();
                $fallback_description = $product->productDescription->first();

                if (filled($product_slug) && filled($variant_slug)) {
                    $product_url = localized_route('localized.catalog.product.variant.show', [
                        'slug'         => $product_slug,
                        'variant_slug' => $variant_slug,
                    ]);
                } else {
                    $product_url = filled($product_slug)
                        ? localized_route('localized.catalog.product.show', ['slug' => $product_slug])
                        : '';
                }

                return [
                    'id'          => (int) $product->id,
                    'name'        => (string) ($variant_description->name ?? $fallback_description->name ?? ''),
                    'sku'         => (string) $product->sku,
                    'quantity'    => (int) $variant_stock,
                    'is_in_stock' => (int) $variant_stock >= $minimum_stock_quantity,
                    'url'         => $product_url,
                    'image_data'  => [
                        'urls' => multiple_convert_img_and_get_url(
                            $variant_image,
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
        $dynamic_price_max    = $this->resolveDynamicPriceRangeMax(
            filter_set            : $filter_set,
            filter_groups         : $filter_groups,
            category_id           : $category_id,
            language_id           : $language_id,
            minimum_stock_quantity: $minimum_stock_quantity,
            validated_data        : $validated_data,
        );

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
                'get_value'   => (string) Arr::get((array) ($group->config ?? []), 'get.value', ''),
                'get_extra'   => is_array(Arr::get((array) ($group->config ?? []), 'get.extra'))
                    ? (array) Arr::get((array) ($group->config ?? []), 'get.extra')
                    : [],
                'items' => [],
            ];

            if ($group_source_type === CatalogFilterGroupSourceTypeEnum::Price->value) {
                $group_config   = is_array($group->config) ? $group->config : [];
                $selected_from  = Arr::get($validated_data, 'price_from');
                $selected_to    = Arr::get($validated_data, 'price_to');
                $price_from_key = trim((string) Arr::get($group_config, 'get.extra.from_key', 'price_from'));
                $price_to_key   = trim((string) Arr::get($group_config, 'get.extra.to_key', 'price_to'));

                if (blank($price_from_key)) {
                    $price_from_key = 'price_from';
                }

                if (blank($price_to_key)) {
                    $price_to_key = 'price_to';
                }

                $group_payload['range'] = [
                    'min' => Arr::get($group_config, 'min_price'),
                    'max' => is_numeric($dynamic_price_max)
                        ? (float) $dynamic_price_max
                        : Arr::get($group_config, 'max_price'),
                    'step'          => Arr::get($group_config, 'step'),
                    'selected_from' => $selected_from,
                    'selected_to'   => $selected_to,
                    'cancel_link'   => $this->buildCancelLinkForPriceRange(
                        price_from_get_key: $price_from_key,
                        price_to_get_key  : $price_to_key,
                        selected_from     : $selected_from,
                        selected_to       : $selected_to,
                    ),
                ];

                $filters_data[$group_key] = $group_payload;

                continue;
            }

            /** @var Collection<int, CatalogFilterValue> $group_values */
            $group_values = $group->values;

            foreach ($group_values as $value) {
                $value_key  = (string) $value->id;
                $is_checked = $this->resolveValueCheckedState(
                    group         : $group,
                    value         : $value,
                    validated_data: $validated_data,
                );

                $group_payload['items'][$value_key] = [
                    'id'             => (int) $value->id,
                    'code'           => (string) $value->code,
                    'name'           => $this->resolveValueLabel($value, $language_id),
                    'total_products' => $this->resolveValueProductsTotal(
                        filter_set_id         : (int) $filter_set->id,
                        active_index_version  : $active_index_version,
                        category_id           : $category_id,
                        group_id              : (int) $group->id,
                        value_id              : (int) $value->id,
                        minimum_stock_quantity: $minimum_stock_quantity,
                        fallback_count        : (int) $value->products_count_cached,
                    ),
                    'is_checked'  => $is_checked,
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
        $query_base                 = $query->toBase();
        $query_base->columns        = [];

        $max_price = $query_base
            ->selectRaw('MAX(' . $effective_price_expression . ') as max_effective_price')
            ->value('max_effective_price');

        return is_numeric($max_price) ? (float) $max_price : null;
    }

    private function buildCancelLinkForCheckedValue(CatalogFilterGroup $group, CatalogFilterValue $value): ?string
    {
        $get_key = trim((string) $group->get_key);

        if (blank($get_key) || ! app()->bound('request')) {
            return null;
        }

        $request          = request();
        $query_parameters = (array) $request->query();
        $query_value      = $this->extractQueryValueByGetKey($query_parameters, $get_key);

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

        $request               = request();
        $next_query_parameters = (array) $request->query();
        $next_query_parameters = $this->replaceQueryValueByGetKey(
            query_parameters: $next_query_parameters,
            get_key         : $price_from_get_key,
            next_value      : null,
        );
        $next_query_parameters = $this->replaceQueryValueByGetKey(
            query_parameters: $next_query_parameters,
            get_key         : $price_to_get_key,
            next_value      : null,
        );

        $next_query_string = Arr::query($next_query_parameters);

        return filled($next_query_string)
            ? $request->url() . '?' . $next_query_string
            : $request->url();
    }

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

                return $query_parameters;
            }

            Arr::set($query_parameters, $normalized_get_key, $next_value);

            return $query_parameters;
        }

        if ($next_value === null) {
            Arr::forget($query_parameters, $get_key);

            return $query_parameters;
        }

        Arr::set($query_parameters, $get_key, $next_value);

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
                $string_value = trim((string) $item);

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
    private function buildEmptyResponse(array $validated_data, string $sort_code): array
    {
        $requested_sort_value = $this->normalizeRequestedSortValue(Arr::get($validated_data, 'sort', ''));

        return [
            'products'        => [],
            'paginator'       => null,
            'applied_filters' => [
                'sort'       => $requested_sort_value,
                'price_from' => Arr::get($validated_data, 'price_from'),
                'price_to'   => Arr::get($validated_data, 'price_to'),
                'attributes' => (array) Arr::get($validated_data, 'attributes', []),
            ],
            'active_sort_code'    => $sort_code,
            'selected_sort_value' => $requested_sort_value,
            'is_filter_enabled'   => false,
            'filters_data'        => [],
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
        return Str::lower(trim((string) $sort_value));
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
