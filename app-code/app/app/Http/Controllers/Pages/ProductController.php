<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ProductController extends Controller
{
    /**
     * @throws Throwable
     */
    public function show(Request $request, string $locale, string $slug, ?string $variant_slug = null): View
    {
        $locale   = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (! $language instanceof Language) {
            throw new NotFoundHttpException();
        }

        $product = Product::findBySlug($slug, (int) $language->id);

        if (! $product instanceof Product) {
            throw new NotFoundHttpException();
        }

        $page_setting      = app(PageSettingsBootstrapService::class)->bootstrapProductPageSetting();
        $page_settings_arr = get_page_settings($page_setting);

        $variant = $this->resolveRequestedVariant($request, $product, (int) $language->id, $variant_slug);

        $header_data = app(HeaderService::class)();
        $page_type   = try_detect_page_type();

        return view('catalog.pages.product', [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type'         => $page_type,
            'breadcrumbs'       => $this->resolveBreadcrumbs($product, $variant, (int) $language->id),
            'product_view_data' => $this->buildProductViewData($product, $variant, (int) $language->id, $page_settings_arr),
            'product'           => $product,
            'variant'           => $variant,
        ]);
    }

    private function resolveRequestedVariant(
        Request $request,
        Product $product,
        int $language_id,
        ?string $variant_slug,
    ): ?ProductVariant {
        if (filled((string) $variant_slug)) {
            $variant_from_slug = ProductVariant::findBySlug((string) $variant_slug, $language_id);

            if ($variant_from_slug instanceof ProductVariant && (int) $variant_from_slug->product_id !== (int) $product->id) {
                throw new NotFoundHttpException();
            }

            if ($variant_from_slug instanceof ProductVariant) {
                return $variant_from_slug;
            }
        }

        $variant_from_attributes = $this->resolveVariantByAttributeQuery($request, $product, $language_id);

        if ($variant_from_attributes instanceof ProductVariant) {
            return $variant_from_attributes;
        }

        return $product->defaultVariant;
    }

    private function resolveVariantByAttributeQuery(Request $request, Product $product, int $language_id): ?ProductVariant
    {
        $attribute_filters = $this->extractAttributeFiltersFromQuery($request->query());

        if ($attribute_filters === []) {
            return null;
        }

        $variant_query = ProductVariant::query()
            ->where('product_id', (int) $product->id);

        foreach ($attribute_filters as $attribute_id => $attribute_values) {
            $variant_query->whereHas('attributeValues', function (Builder $query) use ($attribute_id, $attribute_values, $language_id): void {
                $query->where('attribute_id', $attribute_id)
                    ->where('language_id', $language_id)
                    ->whereIn('value_string', $attribute_values);
            });
        }

        return $variant_query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $query_params
     * @return array<int, array<int, string>>
     */
    private function extractAttributeFiltersFromQuery(array $query_params): array
    {
        return collect($query_params)
            ->mapWithKeys(function (mixed $raw_value, string $query_key): array {
                if (! preg_match('/^attribute_(\d+)$/', $query_key, $matches)) {
                    return [];
                }

                $attribute_id = (int) $matches[1];
                $values       = collect(is_array($raw_value) ? $raw_value : explode(',', (string) $raw_value))
                    ->map(fn (mixed $value): string => trim((string) $value))
                    ->filter(fn (string $value): bool => filled($value))
                    ->unique()
                    ->values()
                    ->all();

                if ($values === []) {
                    return [];
                }

                return [$attribute_id => $values];
            })
            ->all();
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    private function resolveBreadcrumbs(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        $product_title = $this->resolveProductTitle($product, $variant, $language_id);
        $breadcrumbs   = [
            breadcrumb(__('catalog/default.links.home'), localizedRoute('catalog.home')),
        ];

        foreach ($this->resolveProductCategoryBreadcrumbs($product, $language_id) as $category_breadcrumb) {
            $breadcrumbs[] = $category_breadcrumb;
        }

        $breadcrumbs[] = breadcrumb($product_title);

        return $breadcrumbs;
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    private function resolveProductCategoryBreadcrumbs(Product $product, int $language_id): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Category> $product_categories */
        $product_categories = $product->categories()
            ->with([
                'categoryPaths' => fn ($query) => $query->orderBy('level'),
            ])
            ->get();

        if ($product_categories->isEmpty()) {
            return [];
        }

        /** @var Category $target_category */
        $target_category = $product_categories->first();

        /**
         * If product has an explicitly selected default category,
         * use that category as the source of breadcrumb chain.
         */
        if (is_numeric($product->default_category_id)) {
            $default_category = $product_categories
                ->firstWhere('id', (int) $product->default_category_id);

            if ($default_category instanceof Category) {
                $target_category = $default_category;
            }
        }

        foreach ($product_categories as $current_category) {
            /** @var Category $current_category */
            if ((int) $current_category->id === (int) $target_category->id) {
                continue;
            }

            if ((int) $target_category->id === (int) $product->default_category_id) {
                // Keep explicit category priority over automatic selection rules.
                break;
            }

            $selected_depth = $target_category->categoryPaths->count();
            $current_depth  = $current_category->categoryPaths->count();

            if ($current_depth > $selected_depth) {
                $target_category = $current_category;

                continue;
            }

            if ($current_depth < $selected_depth) {
                continue;
            }

            if ((int) $current_category->sort_order < (int) $target_category->sort_order) {
                $target_category = $current_category;

                continue;
            }

            if ((int) $current_category->sort_order > (int) $target_category->sort_order) {
                continue;
            }

            if ((int) $current_category->id < (int) $target_category->id) {
                $target_category = $current_category;
            }
        }

        $path_ids = $target_category->categoryPaths
            ->sortBy('level')
            ->pluck('path_id')
            ->map(fn (mixed $path_id): int => (int) $path_id)
            ->unique()
            ->values()
            ->all();

        if ($path_ids === []) {
            return [];
        }

        /** @var Collection<int, Category> $categories_by_id */
        $categories_by_id = Category::query()
            ->with([
                'categoryDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
                'slugs' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->whereIn('id', $path_ids)
            ->get()
            ->keyBy('id');

        return collect($path_ids)
            ->map(function (int $path_id) use ($categories_by_id): ?array {
                /** @var Category|null $category */
                $category = $categories_by_id->get($path_id);

                if (! $category instanceof Category) {
                    return null;
                }

                $category_title = trim((string) optional($category->categoryDescription->first())->name);
                $category_slug  = trim((string) optional($category->slugs->first())->slug);

                if (blank($category_title)) {
                    return null;
                }

                if (filled($category_slug)) {
                    return breadcrumb($category_title, localizedRoute('localized.catalog.category.show', [
                        'slug' => $category_slug,
                    ]));
                }

                return breadcrumb($category_title);
            })
            ->filter(fn (?array $breadcrumb): bool => $breadcrumb !== null)
            ->values()
            ->all();
    }

    /**
     * Build a stable product payload for Blade and JS consumers.
     *
     * @param  array<string, mixed>  $page_settings_arr
     * @return array<string, mixed>
     */
    private function buildProductViewData(
        Product $product,
        ?ProductVariant $variant,
        int $language_id,
        array $page_settings_arr,
    ): array {
        $product_title   = $this->resolveProductTitle($product, $variant, $language_id);
        $product_sku     = (string) $product->sku;
        $product_price   = $this->resolveProductPrice($product, $variant);
        $formatted_price = format_price(
            $product_price,
            config('app.currency.current_currency_code'),
            (float) config('app.currency.current_exchange_rate'),
        );

        $image_size          = $this->resolveProductCustomerImageSize($page_settings_arr);
        $minimum_stock_qty   = $this->resolveProductMinimumStockQuantity($page_settings_arr);
        $is_in_stock         = $this->resolveInStockState($variant, $minimum_stock_qty);
        $gallery_images      = $this->resolveGalleryImages($product, $variant);
        $main_image_path     = (string) ($gallery_images->first() ?? '');
        $main_image_data     = $this->buildImageData($main_image_path, $image_size);
        $gallery_images_data = $gallery_images
            ->map(fn (string $image_path): array => $this->buildImageData($image_path, $image_size))
            ->values()
            ->all();
        $option_groups    = $this->resolveOptionGroups($variant, $language_id);
        $details_sections = $this->resolveDetailsSections();

        return [
            'title' => $product_title,
            'sku'   => $product_sku,
            'price' => [
                'value'         => $product_price,
                'formatted'     => (string) $formatted_price,
                'currency_code' => (string) config('app.currency.current_currency_code'),
                'exchange_rate' => (float) config('app.currency.current_exchange_rate'),
            ],
            'price_formatted'        => $formatted_price,
            'is_in_stock'            => $is_in_stock,
            'minimum_stock_quantity' => $minimum_stock_qty,
            'main_image'             => $main_image_data,
            'gallery_images_data'    => $gallery_images_data,
            'main_image_path'        => $main_image_path,
            'gallery_images'         => $gallery_images,
            'option_groups'          => $option_groups,
            'details_sections'       => $details_sections,
            'labels'                 => $this->resolveUiLabels(),
        ];
    }

    private function resolveProductTitle(Product $product, ?ProductVariant $variant, int $language_id): string
    {
        $variant_title = $variant?->descriptions()
            ->where('language_id', $language_id)
            ->value('name');

        if (filled((string) $variant_title)) {
            return Str::trim((string) $variant_title);
        }

        $product_title = $product->productDescription()
            ->where('language_id', $language_id)
            ->value('name');

        if (filled((string) $product_title)) {
            return Str::trim((string) $product_title);
        }

        return Str::trim((string) $product->model);
    }

    private function resolveProductPrice(Product $product, ?ProductVariant $variant): float
    {
        if ($variant instanceof ProductVariant && is_numeric($variant->price)) {
            return (float) $variant->price;
        }

        return is_numeric($product->price) ? (float) $product->price : 0.0;
    }

    private function resolveInStockState(?ProductVariant $variant, int $minimum_stock_quantity): bool
    {
        if (! $variant instanceof ProductVariant) {
            return false;
        }

        // Variant minimum is an item-level constraint; page setting minimum is a storefront policy.
        // We enforce the stricter one to keep stock behavior deterministic for customer pages.
        $minimum_quantity = max(
            0,
            max((int) $variant->minimum, $minimum_stock_quantity),
        );

        return $variant->is_active && (int) $variant->quantity >= $minimum_quantity;
    }

    /**
     * @return Collection<int, string>
     */
    private function resolveGalleryImages(Product $product, ?ProductVariant $variant): Collection
    {
        $variant_images = $variant instanceof ProductVariant
            ? $variant->images()
                ->orderBy('sort_order')
                ->pluck('image')
            : collect();

        $variant_images = collect($variant_images)
            ->map(fn (mixed $image): string => $this->normalizeImagePath($image))
            ->filter(fn (string $image): bool => filled($image))
            ->values();

        if ($variant_images->isNotEmpty()) {
            return $variant_images;
        }

        $variant_image  = $variant instanceof ProductVariant ? $variant->image : null;
        $fallback_image = Str::trim((string) ($variant_image ?? $product->image ?? ''));

        return filled($fallback_image)
            ? collect([$fallback_image])
            : collect();
    }

    private function normalizeImagePath(mixed $image): string
    {
        while (is_array($image)) {
            $image = Arr::get($image, 'image', Arr::first($image));
        }

        return Str::trim((string) $image);
    }

    /**
     * @param  array<string, mixed>  $page_settings_arr
     * @return array{width:int,height:int}
     */
    private function resolveProductCustomerImageSize(array $page_settings_arr): array
    {
        return [
            'width' => max(
                1,
                (int) Arr::get(
                    $page_settings_arr,
                    'customer.images.product.width',
                    (int) Arr::get(
                        $page_settings_arr,
                        'images.product.width',
                        (int) config('app.page_settings.product.for_customer.image_width', (int) config('app.page_settings.product.image_width', 500)),
                    ),
                ),
            ),
            'height' => max(
                1,
                (int) Arr::get(
                    $page_settings_arr,
                    'customer.images.product.height',
                    (int) Arr::get(
                        $page_settings_arr,
                        'images.product.height',
                        (int) config('app.page_settings.product.for_customer.image_height', (int) config('app.page_settings.product.image_height', 500)),
                    ),
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $page_settings_arr
     */
    private function resolveProductMinimumStockQuantity(array $page_settings_arr): int
    {
        return max(
            0,
            (int) Arr::get(
                $page_settings_arr,
                'customer.stock.minimum_stock_quantity',
                (int) Arr::get(
                    $page_settings_arr,
                    'stock.minimum_stock_quantity',
                    (int) config('app.page_settings.product.for_customer.minimum_stock_quantity', (int) config('app.page_settings.product.minimum_stock_quantity', 1)),
                ),
            ),
        );
    }

    /**
     * @param  array{width:int,height:int}  $image_size
     * @return array{path:string,urls:array<string, string>,width:int,height:int}
     */
    private function buildImageData(string $image_path, array $image_size): array
    {
        $normalized_path = Str::trim($image_path);

        return [
            'path' => $normalized_path,
            'urls' => filled($normalized_path)
                ? multiple_convert_img_and_get_url($normalized_path, (int) $image_size['width'], (int) $image_size['height'])
                : [],
            'width'  => (int) $image_size['width'],
            'height' => (int) $image_size['height'],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, values: array<int, string>}>
     */
    private function resolveOptionGroups(?ProductVariant $variant, int $language_id): array
    {
        $fallback_groups = $this->resolveFallbackOptionGroups();

        if (! $variant instanceof ProductVariant) {
            return $fallback_groups;
        }

        $attribute_rows = $variant->attributeValues()
            ->with([
                'attribute.attributeDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->orderBy('attribute_id')
            ->get();

        if ($attribute_rows->isEmpty()) {
            return $fallback_groups;
        }

        /** @var Collection<int, ProductVariantAttributeValue> $attribute_rows */
        $groups = $attribute_rows
            ->groupBy(fn (ProductVariantAttributeValue $attribute_value): int => (int) $attribute_value->attribute_id)
            ->map(function (Collection $group_rows): array {
                /** @var ProductVariantAttributeValue|null $first_row */
                $first_row = $group_rows->first();

                $label = trim((string) optional(optional($first_row)->attribute?->attributeDescription->first())->name);

                if (blank($label)) {
                    $label = __('catalog/default.product.option_groups.attribute_fallback');
                }

                return [
                    'key'    => 'attribute_' . (int) optional($first_row)->attribute_id,
                    'label'  => $label,
                    'values' => $group_rows
                        ->map(fn (ProductVariantAttributeValue $row): string => trim((string) $row->value_string))
                        ->filter(fn (string $value): bool => filled($value))
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['values'] !== [])
            ->values()
            ->all();

        return $groups !== [] ? $groups : $fallback_groups;
    }

    /**
     * @return array<int, array{key: string, label: string, values: array<int, string>}>
     */
    private function resolveFallbackOptionGroups(): array
    {
        return [
            ['key' => 'color', 'label' => __('catalog/default.product.option_groups.color'), 'values' => []],
            ['key' => 'length', 'label' => __('catalog/default.product.option_groups.length'), 'values' => []],
            ['key' => 'size', 'label' => __('catalog/default.product.option_groups.size'), 'values' => []],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, string>}>
     */
    private function resolveDetailsSections(): array
    {
        return [
            ['key' => 'composition', 'label' => __('catalog/default.product.details.composition'), 'items' => []],
            ['key' => 'care', 'label' => __('catalog/default.product.details.care'), 'items' => []],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resolveUiLabels(): array
    {
        return [
            'size_help'     => __('catalog/default.product.labels.size_help'),
            'buy_one_click' => __('catalog/default.product.labels.buy_one_click'),
            'add_to_cart'   => __('catalog/default.product.labels.add_to_cart'),
            'notify'        => __('catalog/default.product.labels.notify'),
            'telegram'      => __('catalog/default.product.labels.telegram'),
        ];
    }
}
