<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services\Storefront;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Catalogs\Products\ProductVariantDescription;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\ProductsCarousel\Services\ProductsCarouselProductFilterService;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;
use Random\RandomException;
use Throwable;

/**
 * Resolves storefront-ready ProductsCarousel payload for current placement/page.
 */
readonly class ProductsCarouselStorefrontService
{
    public function __construct(
        private ProductsCarouselConfig $products_carousel_config,
        private ProductsCarouselProductFilterService $products_carousel_product_filter_service,
    ) {
    }

    /**
     * @return array<int, array{
     *     instance_id: int,
     *     name: string,
     *     module_name_for_user: string,
     *     short_description_for_user: string,
     *     page_types: array<int, string>,
     *     placement: string|null,
     *     source_mode: string,
     *     products: array<int, array{
     *         id: int,
     *         name: string,
     *         model: string,
     *         sku: string,
     *         price: string|float,
     *         rrc_price: string|float,
     *         is_discounted: bool,
     *         image_data: array{urls: array<string, string>, width: int, height: int},
     *         url: string|null
     *     }>
     * }>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        try {
            $definitions = resolve_modules_for_context($placement)
                ->filter(fn (ModuleDefinition $definition): bool => $definition->nwidart_name === 'ProductsCarousel');
        } catch (Throwable $e) {
            Log::channel('stack')->error($e->getMessage(), $e->getTrace());

            return [];
        }

        /** @var Collection<int, ModuleDefinition> $definitions */

        $products_carousel_modules = $definitions
            ->map(fn (ModuleDefinition $definition): array => $this->mapDefinitionInstances($definition, $page_type))
            ->collapse()
            ->values();

        /** @var array<int, array<string, mixed>> $resolved_modules */
        $resolved_modules = $products_carousel_modules->all();

        return $resolved_modules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapDefinitionInstances(ModuleDefinition $definition, ?string $page_type): array
    {
        /** @var Collection<int, ModuleInstance> $instances */
        $instances = $definition->instances;

        return $instances
            ->filter(fn (ModuleInstance $instance): bool => $this->matchesPageType($instance, $page_type))
            ->map(function (ModuleInstance $instance): array {
                $instance_settings = is_array($instance->settings) ? $instance->settings : [];
                $source_mode = (string) Arr::get($instance_settings, 'source_mode', 'category_based');
                $runtime_shared_settings = $this->resolveRuntimeSharedSettings($instance_settings);
                $localized_shared_content = $this->resolveLocalizedSharedContent($instance_settings);
                $selected_variant_ids = $this->resolveSelectedVariantIdsForInstance($source_mode, $instance_settings);
                $products = $this->resolveProductsForInstance(
                    $source_mode,
                    $instance_settings,
                    $runtime_shared_settings,
                    $selected_variant_ids,
                );

                $products_payload = $products
                    ->toBase()
                    ->flatMap(fn (Product $product): array => $this->mapProductCards(
                        $product,
                        $runtime_shared_settings['product_image_width'],
                        $runtime_shared_settings['product_image_height'],
                        $selected_variant_ids,
                    ))
                    ->take($runtime_shared_settings['products_limit'])
                    ->values()
                    ->all();

                return [
                    'instance_id' => $instance->id,
                    'name' => $instance->name,
                    'module_name_for_user' => $localized_shared_content['module_name_for_user'],
                    'short_description_for_user' => $localized_shared_content['short_description_for_user'],
                    'page_types' => collect((array) Arr::get($instance_settings, 'shared.page_types', []))
                        ->filter(fn (mixed $page_type): bool => is_string($page_type) && filled($page_type))
                        ->values()
                        ->all(),
                    'placement' => $instance->placement,
                    'source_mode' => $source_mode,
                    'products' => $products_payload,
                ];
            })
            ->filter(fn (array $module_data): bool => $module_data['products'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @return array{
     *      module_name_for_user: string,
     *      short_description_for_user: string,
     *      requested_locale: string,
     *      resolved_locale: string,
     *      fallback_used: bool
     * }
     */
    private function resolveLocalizedSharedContent(array $instance_settings): array
    {
        $requested_locale = app()->getLocale();
        $shared_settings = Arr::get($instance_settings, 'shared', []);
        $shared_settings = is_array($shared_settings) ? $shared_settings : [];
        $translations_payload = Arr::get($shared_settings, 'translations', []);
        $translations_payload = is_array($translations_payload) ? $translations_payload : [];

        $translations_by_locale = collect((array) $translations_payload)
            ->filter(fn (mixed $translation): bool => is_array($translation))
            ->map(function (array $translation): array {
                return [
                    'module_name_for_user' => Str::squish((string) Arr::get($translation, 'module_name_for_user')),
                    'short_description_for_user' => Str::squish((string) Arr::get($translation, 'short_description_for_user')),
                ];
            });

        $resolved_locale = $requested_locale;
        $fallback_used = false;
        $resolved_translation = $translations_by_locale->get($requested_locale, []);
        $resolved_translation = (array) $resolved_translation;

        $requested_has_values = filled((string) Arr::get($resolved_translation, 'module_name_for_user'))
            || filled((string) Arr::get($resolved_translation, 'short_description_for_user'));

        if ($requested_has_values === false) {
            $default_locale = Language::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('code');

            $default_translation = is_string($default_locale)
                ? $translations_by_locale->get($default_locale, [])
                : [];
            $default_translation = (array) $default_translation;

            $default_has_values = filled((string) Arr::get($default_translation, 'module_name_for_user'))
                || filled((string) Arr::get($default_translation, 'short_description_for_user'));

            if ($default_has_values) {
                $resolved_translation = $default_translation;
                $resolved_locale = (string) $default_locale;
                $fallback_used = true;
            }
        }

        $resolved_has_values = filled((string) Arr::get($resolved_translation, 'module_name_for_user'))
            || filled((string) Arr::get($resolved_translation, 'short_description_for_user'));

        if ($resolved_has_values === false) {
            $first_filled_locale = null;

            foreach ($translations_by_locale as $locale_code => $translation) {
                if (
                    filled((string) Arr::get($translation, 'module_name_for_user'))
                    || filled((string) Arr::get($translation, 'short_description_for_user'))
                ) {
                    $first_filled_locale = (string) $locale_code;
                    $resolved_translation = $translation;
                    $fallback_used = true;

                    break;
                }
            }

            if (is_string($first_filled_locale) && filled($first_filled_locale)) {
                $resolved_locale = $first_filled_locale;
            }
        }

        $resolved_has_values = filled((string) Arr::get($resolved_translation, 'module_name_for_user'))
            || filled((string) Arr::get($resolved_translation, 'short_description_for_user'));

        if ($resolved_has_values === false) {
            $legacy_module_name = Str::squish((string) Arr::get($shared_settings, 'module_name_for_user', ''));
            $legacy_description = Str::squish((string) Arr::get($shared_settings, 'short_description_for_user', ''));

            if (filled($legacy_module_name) || filled($legacy_description)) {
                $resolved_translation = [
                    'module_name_for_user' => $legacy_module_name,
                    'short_description_for_user' => $legacy_description,
                ];
                $fallback_used = true;
            }
        }

        return [
            'module_name_for_user' => (string) Arr::get($resolved_translation, 'module_name_for_user', ''),
            'short_description_for_user' => (string) Arr::get($resolved_translation, 'short_description_for_user', ''),
            'requested_locale' => $requested_locale,
            'resolved_locale' => $resolved_locale,
            'fallback_used' => $fallback_used,
        ];
    }

    /**
     * @param array{
     *      min_quantity: int,
     *      products_limit: int,
     *      product_image_width: int,
     *      product_image_height: int,
     *      sort_mode: string,
     *      sort_sequence: array<int, string>
     * }                            $runtime_shared_settings
     * @param  array<string, mixed>  $instance_settings
     * @param array<int, int> $selected_variant_ids
     * @return EloquentCollection<int, Product>
     */
    private function resolveProductsForInstance(
        string $source_mode,
        array $instance_settings,
        array $runtime_shared_settings,
        array $selected_variant_ids,
    ): EloquentCollection {
        return match ($source_mode) {
            'manual_only' => $this->resolveManualOnlyProducts($instance_settings, $runtime_shared_settings, $selected_variant_ids),
            default => $this->resolveCategoryBasedProducts($instance_settings, $runtime_shared_settings, $selected_variant_ids),
        };
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @param array{
     *      min_quantity: int,
     *      products_limit: int,
     *      product_image_width: int,
     *      product_image_height: int,
     *      sort_mode: string,
     *      sort_sequence: array<int, string>
     * }                           $runtime_shared_settings
     * @param array<int, int>       $selected_variant_ids
     * @return EloquentCollection<int, Product>
     */
    private function resolveCategoryBasedProducts(
        array $instance_settings,
        array $runtime_shared_settings,
        array $selected_variant_ids,
    ): EloquentCollection {
        $category_ids = $this->normalizeIds(Arr::get($instance_settings, 'category_based.category_ids', []));

        if ($category_ids === []) {
            return new EloquentCollection();
        }

        $use_selected_products_only = (bool) Arr::get($instance_settings, 'category_based.use_selected_products_only', false);

        $selected_variant_ids = $this->products_carousel_product_filter_service->filterActiveVariantIdsByCategories(
            $selected_variant_ids,
            $category_ids,
        );

        $products_query = $this->buildBaseProductsQuery(
            $runtime_shared_settings['min_quantity'],
            $selected_variant_ids,
        )
            ->whereHas('categories', function (Builder $query) use ($category_ids): void {
                $query->whereIn('categories.id', $category_ids);
            });

        if ($use_selected_products_only) {
            if ($selected_variant_ids === []) {
                return new EloquentCollection();
            }

            $selected_product_ids = ProductVariant::query()
                ->whereIn('id', $selected_variant_ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $selected_variant_ids) . ')')
                ->pluck('product_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $products_query
                ->whereIn('id', $selected_product_ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $selected_product_ids) . ')');
        } else {
            $this->applySortPipeline(
                $products_query,
                $runtime_shared_settings['sort_sequence'],
                $this->resolveLanguageId(),
            );
        }

        $products = $products_query
            ->limit($runtime_shared_settings['products_limit'])
            ->get()
            ->values();

        return new EloquentCollection($products->all());
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @param array{
     *      min_quantity: int,
     *      products_limit: int,
     *      product_image_width: int,
     *      product_image_height: int,
     *      sort_mode: string,
     *      sort_sequence: array<int, string>
     * }                           $runtime_shared_settings
     * @param array<int, int>       $selected_variant_ids
     * @return EloquentCollection<int, Product>
     */
    private function resolveManualOnlyProducts(
        array $instance_settings,
        array $runtime_shared_settings,
        array $selected_variant_ids = [],
    ): EloquentCollection {
        if ($selected_variant_ids === []) {
            $selected_product_ids = $this->products_carousel_product_filter_service->filterActiveProductIds(
                Arr::get($instance_settings, 'manual_only.selected_product_ids', []),
            );

            if ($selected_product_ids === []) {
                return new EloquentCollection();
            }

            return $this->buildBaseProductsQuery($runtime_shared_settings['min_quantity'])
                ->whereIn('id', $selected_product_ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $selected_product_ids) . ')')
                ->limit($runtime_shared_settings['products_limit'])
                ->get()
                ->values();
        }

        $selected_product_ids = ProductVariant::query()
            ->whereIn('id', $selected_variant_ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $selected_variant_ids) . ')')
            ->pluck('product_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $products = $this->buildBaseProductsQuery(
            $runtime_shared_settings['min_quantity'],
            $selected_variant_ids,
        )
            ->whereIn('id', $selected_product_ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $selected_product_ids) . ')')
            ->limit($runtime_shared_settings['products_limit'])
            ->get()
            ->values();

        return new EloquentCollection($products->all());
    }

    /**
     * @param array<int, int> $selected_variant_ids
     * @return Builder<Product>
     */
    private function buildBaseProductsQuery(int $min_quantity, array $selected_variant_ids = []): Builder
    {
        $language_id = $this->resolveLanguageId();
        $discount_scope = function ($query): void {
            $query
                ->where('date_start', '<=', now(config('app.timezone')))
                ->where('date_end', '>=', now(config('app.timezone')))
                ->orderBy('priority')
                ->orderByDesc('updated_at');

            $user_group_id = get_app_settings()?->user_group_id;

            if ($user_group_id === null) {
                $query->whereNull('user_group_id');

                return;
            }

            $query->where('user_group_id', (int) $user_group_id);
        };
        $relations = [
            'productDescription' => function ($query) use ($language_id): void {
                $query->where('language_id', $language_id);
            },
            'slugs' => function ($query) use ($language_id): void {
                $query->where('language_id', $language_id);
            },
            'defaultVariant' => function ($query) use ($language_id, $discount_scope): void {
                $query->where('is_active', true)
                    ->with([
                        'descriptions' => function ($description_query) use ($language_id): void {
                            $description_query->where('language_id', $language_id);
                        },
                        'slugs' => function ($slug_query) use ($language_id): void {
                            $slug_query->where('language_id', $language_id);
                        },
                        'discounts' => $discount_scope,
                    ]);
            },
        ];

        if ($selected_variant_ids !== []) {
            $relations['variants'] = function ($query) use ($language_id, $selected_variant_ids, $discount_scope): void {
                $query
                    ->where('is_active', true)
                    ->whereIn('id', $selected_variant_ids)
                    ->with([
                        'descriptions' => function ($description_query) use ($language_id): void {
                            $description_query->where('language_id', $language_id);
                        },
                        'slugs' => function ($slug_query) use ($language_id): void {
                            $slug_query->where('language_id', $language_id);
                        },
                        'discounts' => $discount_scope,
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id');
            };
        }

        return Product::query()
            ->where('is_active', true)
            ->where('quantity', '>=', $min_quantity)
            ->with($relations);
    }

    /**
     * @param Builder<Product> $products_query
     * @param array<int, string> $sort_sequence
     */
    private function applySortPipeline(Builder $products_query, array $sort_sequence, int $language_id): void
    {
        $product_table = $products_query->getModel()->getTable();
        $name_sort_join_applied = false;

        foreach ($sort_sequence as $sort_option) {
            $direction = Str::endsWith($sort_option, '_asc') ? 'asc' : 'desc';

            if ($sort_option === 'name_asc' || $sort_option === 'name_desc') {
                if ($name_sort_join_applied === false) {
                    $products_query->leftJoin('product_descriptions as products_carousel_sort_description', function ($join) use ($language_id, $product_table): void {
                        $join->on('products_carousel_sort_description.product_id', '=', $product_table . '.id')
                            ->where('products_carousel_sort_description.language_id', '=', $language_id);
                    });

                    $products_query->addSelect($product_table . '.*');
                    $name_sort_join_applied = true;
                }

                $products_query
                    ->orderBy('products_carousel_sort_description.name', $direction)
                    ->orderBy($product_table . '.id', $direction);

                continue;
            }

            if ($sort_option === 'price_asc' || $sort_option === 'price_desc') {
                $products_query
                    ->orderBy('price', $direction)
                    ->orderBy($product_table . '.id', $direction);

                continue;
            }

            if ($sort_option === 'quantity_asc' || $sort_option === 'quantity_desc') {
                $products_query
                    ->orderBy('quantity', $direction)
                    ->orderBy($product_table . '.id', $direction);

                continue;
            }

            if ($sort_option === 'date_added_asc' || $sort_option === 'date_added_desc') {
                $products_query
                    ->orderBy('date_added', $direction)
                    ->orderBy($product_table . '.id', $direction);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     *
     * @throws RandomException
     *
     * @return array{
     *      min_quantity: int,
     *      products_limit: int,
     *      product_image_width: int,
     *      product_image_height: int,
     *      sort_mode: string,
     *      sort_sequence: array<int, string>
     * }
     */
    private function resolveRuntimeSharedSettings(array $instance_settings): array
    {
        $shared_settings = Arr::get($instance_settings, 'shared', []);

        if (! is_array($shared_settings)) {
            $shared_settings = [];
        }

        $sort_mode = (string) Arr::get(
            $shared_settings,
            'sort_mode',
            $this->products_carousel_config->get('settings.default_sort_mode', 'custom'),
        );

        $allowed_sort_modes = $this->getAllowedSortModes();

        if (! in_array($sort_mode, $allowed_sort_modes, true)) {
            $sort_mode = (string) $this->products_carousel_config->get('settings.default_sort_mode', 'custom');
        }

        $min_quantity = max(
            (int) Arr::get($shared_settings, 'min_quantity', $this->products_carousel_config->get('settings.default_min_quantity', 1)),
            1,
        );
        $products_limit = max(
            (int) Arr::get($shared_settings, 'products_limit', $this->products_carousel_config->get('settings.default_products_limit', 15)),
            1,
        );
        $product_image_width = max(
            (int) Arr::get($shared_settings, 'product_image_width', $this->products_carousel_config->get('settings.default_image_width', 420)),
            1,
        );
        $product_image_height = max(
            (int) Arr::get($shared_settings, 'product_image_height', $this->products_carousel_config->get('settings.default_image_height', 420)),
            1,
        );

        $sort_sequence = $sort_mode === 'random'
            ? $this->generateRandomSortSequence()
            : $this->resolveCustomSortSequence($instance_settings);

        return [
            'min_quantity' => $min_quantity,
            'products_limit' => $products_limit,
            'product_image_width' => $product_image_width,
            'product_image_height' => $product_image_height,
            'sort_mode' => $sort_mode,
            'sort_sequence' => $sort_sequence,
        ];
    }

    /**
     * @param array<string, mixed> $instance_settings
     * @return array<int, string>
     */
    private function resolveCustomSortSequence(array $instance_settings): array
    {
        $allowed_sort_options = $this->getAllowedSortOptions();

        $custom_sort_options = collect((array) Arr::get($instance_settings, 'shared.custom_sort', []))
            ->filter(function (mixed $option, mixed $key) use ($allowed_sort_options): bool {
                if (! is_string($option) || ! is_string($key)) {
                    return false;
                }

                return in_array("{$key}_$option", $allowed_sort_options, true);
            })
            ->map(function (mixed $option, mixed $key): string {
                if (! is_string($option) || ! is_string($key)) {
                    return '';
                }

                return "{$key}_$option";
            })
            ->filter(fn (string $sort_option): bool => filled($sort_option))
            ->values()
            ->all();

        $custom_sort_options = $this->removeConflictingSortOptions($custom_sort_options);

        if ($custom_sort_options !== []) {
            return $custom_sort_options;
        }

        Log::channel('stack')->warning('ProductsCarousel custom sort mode has no valid options. Using fallback sort.', [
            'fallback_sort_option' => 'date_added_desc',
        ]);

        return ['quantity_desc', 'date_added_desc'];
    }

    /**
     * @throws RandomException
     *
     * @return array<int, string>
     */
    private function generateRandomSortSequence(): array
    {
        $grouped_sort_options = [
            'price' => ['price_asc', 'price_desc'],
            'name' => ['name_asc', 'name_desc'],
            'date_added' => ['date_added_asc', 'date_added_desc'],
            'quantity' => ['quantity_asc', 'quantity_desc'],
        ];

        $randomized_fields = collect(array_keys($grouped_sort_options))
            ->shuffle()
            ->take(random_int(1, count($grouped_sort_options)))
            ->values();

        return $randomized_fields
            ->map(function (string $field) use ($grouped_sort_options): string {
                $field_options = $grouped_sort_options[$field];

                return $field_options[random_int(0, count($field_options) - 1)];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $sort_options
     * @return array<int, string>
     */
    private function removeConflictingSortOptions(array $sort_options): array
    {
        return collect($sort_options)
            ->unique(function (string $sort_option): string {
                return Str::beforeLast($sort_option, '_');
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getAllowedSortModes(): array
    {
        return collect((array) $this->products_carousel_config->get('settings.allowed_sort_modes', []))
            ->filter(fn (mixed $mode): bool => is_string($mode) && filled($mode))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getAllowedSortOptions(): array
    {
        $sort_options = $this->products_carousel_config->get('settings.allowed_sort_options', []);

        if ($sort_options === []) {
            return [];
        }

        return collect((array) $sort_options)
            ->filter(fn (mixed $sort_option): bool => filled($sort_option) && is_string($sort_option))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @return array<int>
     */
    private function resolveSelectedVariantIdsForInstance(string $source_mode, array $instance_settings): array
    {
        $settings_path = $source_mode === 'manual_only'
            ? 'manual_only'
            : 'category_based';
        $use_selected_products_only = $source_mode !== 'manual_only'
            && (bool) Arr::get($instance_settings, 'category_based.use_selected_products_only', false);

        if ($source_mode !== 'manual_only' && $use_selected_products_only === false) {
            return [];
        }

        $selected_variant_ids = $this->normalizeIds(
            Arr::get($instance_settings, $settings_path . '.selected_variant_ids', []),
        );

        if ($selected_variant_ids !== [] || Arr::has($instance_settings, $settings_path . '.selected_variant_ids')) {
            return $this->products_carousel_product_filter_service->filterActiveVariantIds($selected_variant_ids);
        }

        $legacy_product_ids = $this->normalizeIds(
            Arr::get($instance_settings, $settings_path . '.selected_product_ids', []),
        );

        if ($legacy_product_ids === []) {
            return [];
        }

        return ProductVariant::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->whereIn('product_id', $legacy_product_ids)
            ->whereHas('product', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int>  $selected_variant_ids
     * @return array<int, array<string, mixed>>
     */
    private function mapProductCards(
        Product $product,
        int $product_image_width,
        int $product_image_height,
        array $selected_variant_ids,
    ): array {
        if ($selected_variant_ids === [] || ! $product->relationLoaded('variants')) {
            return [$this->mapProductCard($product, $product_image_width, $product_image_height)];
        }

        $selected_variants = $product->variants
            ->filter(fn (ProductVariant $variant): bool => in_array((int) $variant->id, $selected_variant_ids, true))
            ->sortBy(fn (ProductVariant $variant): int => (int) array_search((int) $variant->id, $selected_variant_ids, true))
            ->values();

        return $selected_variants
            ->map(fn (ProductVariant $variant): array => $this->mapProductCard(
                $product,
                $product_image_width,
                $product_image_height,
                $variant,
            ))
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     variant_id: int,
     *     name: string,
     *     model: string,
     *     sku: string,
     *     price: string|float,
     *     rrc_price: string|float,
     *     is_discounted: bool,
     *     image_data: array{urls: array<string, string>, width: int, height: int},
     *     url: string|null
     * }
     */
    private function mapProductCard(
        Product $product,
        int $product_image_width,
        int $product_image_height,
        ?ProductVariant $selected_variant = null,
    ): array {
        $product_variant = $selected_variant ?? $product->defaultVariant;
        $product_variant_description = $product_variant instanceof ProductVariant
            ? $product_variant->descriptions->first()
            : null;
        $product_description = $product->productDescription->first();
        $slug = $product->slugs->first()?->slug;
        $variant_slug = $product_variant instanceof ProductVariant
            ? $product_variant->slugs->first()?->slug
            : null;
        $image = $product_variant instanceof ProductVariant && filled($product_variant->image)
            ? $product_variant->image
            : $product->image;
        $rrc_price = (float) ($product_variant instanceof ProductVariant ? $product_variant->price : $product->price);
        $discount_price = $product_variant?->discounts->first()?->price;
        $discount_price = is_numeric($discount_price) ? (float) $discount_price : null;
        $price = $discount_price ?? $rrc_price;

        return [
            'id' => (int) $product->id,
            'variant_id' => (int) ($product_variant->id ?? 0),
            'name' => escape_special_html((string) ($product_variant_description instanceof ProductVariantDescription
                ? $product_variant_description->name
                : $product_description?->name)),
            'model' => escape_special_html((string) $product->model),
            'sku' => escape_special_html((string) $product->sku),
            'price' => format_price(
                $price,
                config('app.currency.current_currency_code'),
                (float) config('app.currency.current_exchange_rate'),
            ),
            'rrc_price' => format_price(
                $rrc_price,
                config('app.currency.current_currency_code'),
                (float) config('app.currency.current_exchange_rate'),
            ),
            'is_discounted' => $discount_price !== null && $discount_price < $rrc_price,
            'image_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    (string) $image,
                    $product_image_width,
                    $product_image_height,
                    is_square: false,
                ),
                'width' => $product_image_width,
                'height' => $product_image_height,
            ],
            'url' => filled($slug) && filled($variant_slug)
                ? localized_route('localized.catalog.product.variant.show', [
                    'slug' => $slug,
                    'variant_slug' => $variant_slug,
                ])
                : (filled($slug)
                    ? localized_route('localized.catalog.product.show', ['slug' => $slug])
                    : null),
        ];
    }

    private function matchesPageType(ModuleInstance $instance, ?string $page_type): bool
    {
        if (blank($page_type)) {
            return true;
        }

        $instance_settings = is_array($instance->settings) ? $instance->settings : [];
        $page_types = collect((array) Arr::get($instance_settings, 'shared.page_types', []));

        if ($page_types->isEmpty()) {
            return true;
        }

        return $page_types->contains($page_type);
    }

    /**
     * @param  array<int|string, mixed>|mixed  $ids
     * @return array<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            $ids = [$ids];
        }

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
