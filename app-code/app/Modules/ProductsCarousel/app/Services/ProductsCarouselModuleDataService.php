<?php

declare(strict_types=1);

namespace Modules\ProductsCarousel\Services;

use App\Models\Catalogs\Products\Product;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use App\Models\Settings\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\ProductsCarousel\Support\ProductsCarouselConfig;

/**
 * Resolves storefront-ready ProductsCarousel payload for current placement/page.
 */
readonly class ProductsCarouselModuleDataService
{
    public function __construct(
        private ProductsCarouselConfig $products_carousel_config,
        private ProductsCarouselProductSearchService $products_carousel_product_search_service,
    ) {}

    /**
     * @return array<int, array{
     *     instance_id: int,
     *     name: string,
     *     placement: string|null,
     *     source_mode: string,
     *     products: array<int, array{
     *         id: int,
     *         name: string,
     *         model: string,
     *         sku: string,
     *         price: string|float,
     *         image_data: array{urls: array<string, string>, width: int, height: int},
     *         url: string|null
     *     }>
     * }>
     */
    public function resolveForPlacement(string $placement, ?string $page_type = null): array
    {
        /** @var Collection<int, ModuleDefinition> $definitions */
        $definitions = resolve_modules_for_context($placement)
            ->filter(fn (ModuleDefinition $definition): bool => $definition->nwidart_name === 'ProductsCarousel');

        $products_carousel_modules = $definitions
            ->map(fn (ModuleDefinition $definition): array => $this->mapDefinitionInstances($definition, $page_type))
            ->collapse()
            ->values();

        if ($products_carousel_modules->isEmpty()) {
            Log::channel('stack')->warning('No active products carousel modules were resolved for placement.', [
                'placement' => $placement,
                'page_type' => $page_type,
            ]);
        }

        Log::channel('daily')->info('Products carousel modules resolved for storefront context.', [
            'placement'              => $placement,
            'page_type'              => $page_type,
            'resolved_modules_count' => $products_carousel_modules->count(),
        ]);

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
                $source_mode       = (string) Arr::get($instance_settings, 'source_mode', 'category_based');
                $products          = $this->resolveProductsForInstance($instance, $source_mode, $instance_settings);

                if ($products->isEmpty()) {
                    Log::channel('stack')->warning('ProductsCarousel instance resolved without products.', [
                        'instance_id' => $instance->id,
                        'source_mode' => $source_mode,
                    ]);
                }

                $products_payload = $products
                    ->map(fn (Product $product): array => $this->mapProductCard($product))
                    ->values()
                    ->all();

                Log::channel('daily')->info('ProductsCarousel instance payload prepared.', [
                    'instance_id'        => $instance->id,
                    'source_mode'        => $source_mode,
                    'loaded_items_count' => count($products_payload),
                ]);

                return [
                    'instance_id' => $instance->id,
                    'name'        => $instance->name,
                    'placement'   => $instance->placement,
                    'source_mode' => $source_mode,
                    'products'    => $products_payload,
                ];
            })
            ->filter(fn (array $module_data): bool => $module_data['products'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @return EloquentCollection<int, Product>
     */
    private function resolveProductsForInstance(ModuleInstance $instance, string $source_mode, array $instance_settings): EloquentCollection
    {
        return match ($source_mode) {
            'manual_only' => $this->resolveManualOnlyProducts($instance, $instance_settings),
            default       => $this->resolveCategoryBasedProducts($instance, $instance_settings),
        };
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @return EloquentCollection<int, Product>
     */
    private function resolveCategoryBasedProducts(ModuleInstance $instance, array $instance_settings): EloquentCollection
    {
        $category_ids = $this->normalizeIds(Arr::get($instance_settings, 'category_based.category_ids', []));

        if ($category_ids === []) {
            Log::channel('stack')->warning('ProductsCarousel category-based mode has no categories selected.', [
                'instance_id' => $instance->id,
            ]);

            return new EloquentCollection();
        }

        $use_selected_products_only = (bool) Arr::get($instance_settings, 'category_based.use_selected_products_only', false);

        $selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIdsByCategories(
            Arr::get($instance_settings, 'category_based.selected_product_ids', []),
            $category_ids,
        );

        $products_query = $this->buildBaseProductsQuery()
            ->whereHas('categories', function (Builder $query) use ($category_ids): void {
                $query->whereIn('categories.id', $category_ids);
            });

        if ($use_selected_products_only) {
            if ($selected_product_ids === []) {
                return new EloquentCollection();
            }

            $products_query
                ->whereIn('id', $selected_product_ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $selected_product_ids) . ')');
        } else {
            $products_query->orderByDesc('date_added')->orderByDesc('id');
        }

        $result_limit = max((int) $this->products_carousel_config->get('search.result_limit', 30), 1);

        $products = $products_query
            ->limit($result_limit)
            ->get()
            ->filter(fn (mixed $product): bool => $product instanceof Product)
            ->values();

        Log::channel('daily')->info('ProductsCarousel category-based products resolved.', [
            'instance_id'                => $instance->id,
            'categories_count'           => count($category_ids),
            'use_selected_products_only' => $use_selected_products_only,
            'selected_product_ids_count' => count($selected_product_ids),
            'resolved_products_count'    => $products->count(),
        ]);

        return new EloquentCollection($products->all());
    }

    /**
     * @param  array<string, mixed>  $instance_settings
     * @return EloquentCollection<int, Product>
     */
    private function resolveManualOnlyProducts(ModuleInstance $instance, array $instance_settings): EloquentCollection
    {
        $selected_product_ids = $this->products_carousel_product_search_service->filterActiveProductIds(
            Arr::get($instance_settings, 'manual_only.selected_product_ids', []),
        );

        if ($selected_product_ids === []) {
            Log::channel('stack')->warning('ProductsCarousel manual-only mode has no selected active products.', [
                'instance_id' => $instance->id,
            ]);

            return new EloquentCollection();
        }

        $products = $this->buildBaseProductsQuery()
            ->whereIn('id', $selected_product_ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $selected_product_ids) . ')')
            ->get()
            ->filter(fn (mixed $product): bool => $product instanceof Product)
            ->values();

        Log::channel('daily')->info('ProductsCarousel manual-only products resolved.', [
            'instance_id'                => $instance->id,
            'selected_product_ids_count' => count($selected_product_ids),
            'resolved_products_count'    => $products->count(),
        ]);

        return new EloquentCollection($products->all());
    }

    private function buildBaseProductsQuery(): Builder
    {
        $language_id = $this->resolveLanguageId();

        return Product::query()
            ->where('is_active', true)
            ->with([
                'productDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'slugs' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ]);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     model: string,
     *     sku: string,
     *     price: string|float,
     *     image_data: array{urls: array<string, string>, width: int, height: int},
     *     url: string|null
     * }
     */
    private function mapProductCard(Product $product): array
    {
        $product_description = $product->productDescription->first();
        $slug                = $product->slugs->first()?->slug;
        $image_size_data     = $this->resolveProductImageSize();

        return [
            'id'    => (int) $product->id,
            'name'  => escape_special_html((string) $product_description?->name),
            'model' => escape_special_html((string) $product->model),
            'sku'   => escape_special_html((string) $product->sku),
            'price' => format_price(
                (float) $product->price,
                config('app.currency.default_currency_code'),
                (float) config('app.currency.default_exchange_rate'),
            ),
            'image_data' => [
                'urls' => multiple_convert_img_and_get_url(
                    (string) $product->image,
                    $image_size_data['width'],
                    $image_size_data['height'],
                    is_square: false,
                ),
                'width'  => $image_size_data['width'],
                'height' => $image_size_data['height'],
            ],
            'url' => filled($slug)
                ? localizedRoute('localized.catalog.product.show', ['slug' => $slug])
                : null,
        ];
    }

    private function matchesPageType(ModuleInstance $instance, ?string $page_type): bool
    {
        if (blank($page_type)) {
            return true;
        }

        $instance_settings = is_array($instance->settings) ? $instance->settings : [];
        $page_types        = collect(Arr::get($instance_settings, 'shared.page_types', []));

        if ($page_types->isEmpty()) {
            return true;
        }

        return $page_types->contains($page_type);
    }

    /**
     * @return array{width: int, height: int}
     */
    private function resolveProductImageSize(): array
    {
        $app_settings       = get_app_settings();
        $search_image_sizes = $app_settings?->image_sizes?->firstWhere('name', 'search_product') ?? [];

        return [
            'width'  => max((int) Arr::get($search_image_sizes, 'width', 420), 1),
            'height' => max((int) Arr::get($search_image_sizes, 'height', 420), 1),
        ];
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

        $default_language = new Language()->getDefaultLanguage();

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
