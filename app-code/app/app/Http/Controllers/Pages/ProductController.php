<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductSizeGuide as ProductSizeGuideModel;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use App\Models\Catalogs\Products\ProductVariantDiscount;
use App\Models\Catalogs\Products\ProductVariantSizeGuide;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Services\Trait\SocialServiceTrait;
use App\Supports\Services\Products\ProductSizeGuide;
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
    use SocialServiceTrait;

    /**
     * @throws Throwable
     */
    public function show(Request $request, string $locale, string $slug, ?string $variant_slug = null): View
    {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (!$language instanceof Language) {
            throw new NotFoundHttpException();
        }

        $product = Product::findBySlug($slug, (int)$language->id);

        if (!$product instanceof Product) {
            throw new NotFoundHttpException();
        }

        $variant = $this->resolveRequestedVariant($request, $product, (int)$language->id, $variant_slug);
        return $this->renderProductPage($request, $locale, $product, $variant, $slug, $variant_slug, (int)$language->id);
    }

    public function showStatic(Request $request, string $locale, int $product_id, int $variant_id): View
    {
        $locale = normalize_locale($locale);
        $language = resolve_language_by_locale($locale);

        if (!$language instanceof Language) {
            throw new NotFoundHttpException();
        }

        $product = Product::query()->find($product_id);
        $variant = ProductVariant::query()
            ->whereKey($variant_id)
            ->where('product_id', $product_id)
            ->first();

        if (!$product instanceof Product || !$variant instanceof ProductVariant) {
            throw new NotFoundHttpException();
        }

        return $this->renderProductPage(
            request: $request,
            locale: $locale,
            product: $product,
            variant: $variant,
            slug: null,
            variant_slug: null,
            language_id: (int)$language->id,
        );
    }

    private function renderProductPage(
        Request $request,
        string $locale,
        Product $product,
        ?ProductVariant $variant,
        ?string $slug,
        ?string $variant_slug,
        int $language_id,
    ): View {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapProductPageSetting();
        $page_settings_arr = get_page_settings($page_setting);
        $attribute_filters = prepare_product_attrs((array)$request->query());
        $header_data = app(HeaderService::class)([
            'sluggable_type' => ProductVariant::class,
            'slug' => $slug ?? '',
            'variant_slug' => $variant_slug,
            'attribute_filters' => $attribute_filters,
        ]);
        $page_type = try_detect_page_type();
        $app_settings = get_app_settings();
        $telegram_row = collect((array) ($app_settings?->socials[$locale] ?? []))
            ->first(fn (mixed $social_item): bool => (string)data_get($social_item, 'social_type') === 'telegram');
        $telegram_link = $this->normalizeSocialUrl(data_get($telegram_row, 'url'), $locale);

        $data = [
            'header_data' => $header_data,
            'footer_data' => app(FooterService::class)([
                // Footer uses category links too; pass already loaded categories from header.
                'categories' => $header_data['categories'],
            ]),
            'page_type' => $page_type,
            'breadcrumbs' => $this->resolveBreadcrumbs($product, $variant, $language_id),
            'product_view_data' => $this->buildProductViewData($product, $variant, $language_id, $page_settings_arr),
            'product' => $product,
            'variant' => $variant,
            'telegram_data' => [
                'url' => $telegram_link,
                'text' => __('storefront/default.product.labels.telegram'),
            ],
        ];

        return view('storefront.pages.product', $data);
    }

    private function resolveRequestedVariant(
        Request $request,
        Product $product,
        int $language_id,
        ?string $variant_slug,
    ): ?ProductVariant {
        if (filled((string)$variant_slug)) {
            $variant_from_slug = ProductVariant::findBySlug((string)$variant_slug, $language_id);

            if (
                !$variant_from_slug instanceof ProductVariant ||
                (int)$variant_from_slug->product_id !== (int)$product->id
            ) {
                throw new NotFoundHttpException();
            }

            return $variant_from_slug;
        }

        $variant_from_attributes = $this->resolveVariantByAttributeQuery($request, $product, $language_id);

        if ($variant_from_attributes instanceof ProductVariant) {
            return $variant_from_attributes;
        }

        return $product->defaultVariant;
    }

    private function resolveVariantByAttributeQuery(Request $request, Product $product, int $language_id): ?ProductVariant
    {
        $attribute_filters = prepare_product_attrs($request->query());

        if ($attribute_filters === []) {
            return null;
        }

        $variant_query = ProductVariant::query()
            ->where('product_id', (int)$product->id);

        foreach ($attribute_filters as $attribute_id => $attribute_value_ids) {
            $variant_query->whereHas('attributeValues', function (Builder $query) use ($attribute_id, $attribute_value_ids, $language_id): void {
                $query->where('attribute_id', $attribute_id)
                    ->where('language_id', $language_id)
                    ->whereIn('id', $attribute_value_ids);
            });
        }

        return $variant_query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<int, array{title: string, url: string|null}>
     */
    private function resolveBreadcrumbs(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        $product_title = $this->resolveProductTitle($product, $variant, $language_id);
        $breadcrumbs = [
            breadcrumb(__('storefront/default.links.home'), localized_route('catalog.home')),
        ];

        foreach ($this->resolveProductCategoryBreadcrumbs($product, $language_id) as $category_breadcrumb) {
            $breadcrumbs[] = $category_breadcrumb;
        }

        $breadcrumbs[] = breadcrumb($product_title);

        return $breadcrumbs;
    }

    /**
     * @return array<int, array{title: string, url: string|null}>
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
                ->firstWhere('id', (int)$product->default_category_id);

            if ($default_category instanceof Category) {
                $target_category = $default_category;
            }
        }

        foreach ($product_categories as $current_category) {
            /** @var Category $current_category */
            if ((int)$current_category->id === (int)$target_category->id) {
                continue;
            }

            if ((int)$target_category->id === (int)$product->default_category_id) {
                // Keep explicit category priority over automatic selection rules.
                break;
            }

            $selected_depth = $target_category->categoryPaths->count();
            $current_depth = $current_category->categoryPaths->count();

            if ($current_depth > $selected_depth) {
                $target_category = $current_category;

                continue;
            }

            if ($current_depth < $selected_depth) {
                continue;
            }

            if ((int)$current_category->sort_order < (int)$target_category->sort_order) {
                $target_category = $current_category;

                continue;
            }

            if ((int)$current_category->sort_order > (int)$target_category->sort_order) {
                continue;
            }

            if ((int)$current_category->id < (int)$target_category->id) {
                $target_category = $current_category;
            }
        }

        $path_ids = $target_category->categoryPaths
            ->sortBy('level')
            ->pluck('path_id')
            ->map(fn (mixed $path_id): int => (int)$path_id)
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

                if (!$category instanceof Category) {
                    return null;
                }

                $category_title = Str::trim((string)optional($category->categoryDescription->first())->name);
                $category_slug = Str::trim((string)optional($category->slugs->first())->slug);

                if (blank($category_title)) {
                    return null;
                }

                if (filled($category_slug)) {
                    return breadcrumb($category_title, localized_route('localized.catalog.category.show', [
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
     * @param array<string, mixed> $page_settings_arr
     *
     * @return array<string, mixed>
     */
    private function buildProductViewData(
        Product $product,
        ?ProductVariant $variant,
        int $language_id,
        array $page_settings_arr,
    ): array {
        $product_meta_data = $this->resolveProductMetaData($product, $variant, $language_id);
        $product_title = (string)Arr::get($product_meta_data, 'name', '');
        $product_sku = (string)$product->sku;
        $product_price_data = $this->resolveProductPriceData($product, $variant);
        $formatted_price = replace_currency_symbol_to_code(
            format_price(
                $product_price_data['price'],
                config('app.currency.current_currency_code'),
                (float)config('app.currency.current_exchange_rate'),
            ),
        );
        $formatted_rrc_price = replace_currency_symbol_to_code(
            format_price(
                $product_price_data['rrc_price'],
                config('app.currency.current_currency_code'),
                (float)config('app.currency.current_exchange_rate'),
            ),
        );

        $image_size = $this->resolveProductCustomerImageSize($page_settings_arr);
        $minimum_stock_qty = $this->resolveProductMinimumStockQuantity($page_settings_arr);
        $is_in_stock = $this->resolveInStockState($variant, $minimum_stock_qty);
        $gallery_images = $this->resolveGalleryImages($product, $variant);
        $main_image_path = (string)($gallery_images->first() ?? '');
        $main_image_data = $this->buildImageData($main_image_path, $image_size);
        $option_groups = $this->resolveOptionGroups($product, $variant, $language_id);
        $details_sections = $this->resolveDetailsSections($product, $variant, $language_id);
        $size_guide_data = $this->resolveSizeGuideData($product, $variant, $language_id);
        $gallery_images_data = [];

        // Galler images contains main image as first item, so only build gallery data if there's more than one image to avoid redundant processing
        if ($gallery_images->count() > 1) {
            $gallery_images_data = $gallery_images
                ->map(fn (string $image_path): array => $this->buildImageData($image_path, $image_size))
                ->values()
                ->all();
        }

        return [
            'title' => $product_title,
            'description' => escape_special_html((string)Arr::get($product_meta_data, 'description', '')),
            // In HTML the short_description is not decoded!
            'meta_title' => Str::trim(strip_tags((string)Arr::get($product_meta_data, 'meta_title', ''))),
            'meta_description' => Str::trim(strip_tags((string)Arr::get($product_meta_data, 'meta_description', ''))),
            'meta_keywords' => Str::trim(strip_tags((string)Arr::get($product_meta_data, 'meta_keywords', ''))),
            'sku' => $product_sku,
            'price_formatted' => $formatted_price,
            'rrc_price_formatted' => $formatted_rrc_price,
            'is_discounted' => $product_price_data['is_discounted'],
            'is_in_stock' => $is_in_stock,
            'minimum_stock_quantity' => $minimum_stock_qty,
            'main_image' => $main_image_data,
            'gallery_images_data' => $gallery_images_data,
            'main_image_path' => $main_image_path,
            'gallery_images' => $gallery_images,
            'option_groups' => $option_groups,
            'details_sections' => $details_sections,
            'composition_and_care' => [
                'sections' => $details_sections,
            ],
            'size_guide' => $size_guide_data,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSizeGuideData(Product $product, ?ProductVariant $variant, int $language_id): ?array
    {
        $size_guide = $variant?->sizeGuides()
            ->where('language_id', $language_id)
            ->first();

        $translation = $this->buildSizeGuideTranslation($size_guide);

        if (!$this->hasSizeGuideContent($translation)) {
            $translation = $this->buildSizeGuideTranslation(
                $product->sizeGuides()
                    ->where('language_id', $language_id)
                    ->first(),
            );
        }

        return $this->hasSizeGuideContent($translation)
            ? $this->normalizeSizeGuideTranslationPayload($translation)
            : null;
    }

    /**
     * @param ProductSizeGuideModel|ProductVariantSizeGuide|null $size_guide
     *
     * @return array<string, mixed>
     */
    private function buildSizeGuideTranslation(ProductSizeGuideModel|ProductVariantSizeGuide|null $size_guide): array
    {
        if ($size_guide === null) {
            return [];
        }

        return [
            'title' => $size_guide->short_title,
            'short_description' => $size_guide->short_description,
            'table_rows' => $size_guide->table_rows,
            'image' => $size_guide->image,
            'image_width' => $size_guide->image_width,
            'image_height' => $size_guide->image_height,
            'full_description_title' => $size_guide->full_description_title,
            'full_description' => $size_guide->full_description,
        ];
    }

    /**
     * @param array<string, mixed>|null $translation_data
     */
    private function hasSizeGuideContent(?array $translation_data): bool
    {
        if (!is_array($translation_data)) {
            return false;
        }

        $text_fields = [
            Str::trim((string)Arr::get($translation_data, 'title', '')),
            Str::trim((string)Arr::get($translation_data, 'short_description', '')),
            Str::trim((string)Arr::get($translation_data, 'full_description_title', '')),
            Str::trim((string)Arr::get($translation_data, 'full_description', '')),
        ];

        if (collect($text_fields)->contains(fn (string $value): bool => $value !== '')) {
            return true;
        }

        $table_rows = Arr::get($translation_data, 'table_rows');

        if ($this->hasSizeGuideTableValues($table_rows)) {
            return true;
        }

        return Str::trim((string)Arr::get($translation_data, 'image', '')) !== '';
    }

    private function hasSizeGuideTableValues(mixed $table_rows): bool
    {
        return $this->normalizeSizeGuideTableRows($table_rows) !== [];
    }

    /**
     * @param array<string, mixed> $translation_data
     *
     * @return array<string, mixed>
     */
    private function normalizeSizeGuideTranslationPayload(array $translation_data): array
    {
        $image_path = Str::trim((string)Arr::get($translation_data, 'image', ''));
        $image_size = [
            'width' => max(1, (int)Arr::get($translation_data, 'image_width', 1)),
            'height' => max(1, (int)Arr::get($translation_data, 'image_height', 1)),
        ];

        return [
            'title' => Str::trim((string)Arr::get($translation_data, 'title', '')),
            // In HTML the short_description is not decoded!
            'short_description' => escape_special_html(
                Str::trim((string)Arr::get($translation_data, 'short_description', '')),
            ),
            'table_rows' => $this->normalizeSizeGuideTableRows(Arr::get($translation_data, 'table_rows')),
            'image' => $this->buildImageData($image_path, $image_size),
            'full_description_title' => Str::trim((string)Arr::get($translation_data, 'full_description_title', '')),
            // In HTML the short_description is not decoded!
            'full_description' => escape_special_html(
                Str::trim((string)Arr::get($translation_data, 'full_description', '')),
            ),
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function normalizeSizeGuideTableRows(mixed $table_rows): array
    {
        if (is_string($table_rows)) {
            return ProductSizeGuide::parseSizeGuideTableRowsFromString($table_rows);
        }

        if (!is_array($table_rows)) {
            return [];
        }

        return collect($table_rows)
            ->map(function (mixed $row): array {
                $cells = Arr::get($row, 'cells', []);

                if (!is_array($cells)) {
                    return [];
                }

                return collect($cells)
                    ->map(fn (mixed $cell): string => Str::trim((string)Arr::get($cell, 'value', '')))
                    ->filter(fn (string $value): bool => $value !== '')
                    ->values()
                    ->all();
            })
            ->filter(fn (array $cells): bool => $cells !== [])
            ->values()
            ->all();
    }

    private function resolveProductTitle(Product $product, ?ProductVariant $variant, int $language_id): string
    {
        return (string)Arr::get($this->resolveProductMetaData($product, $variant, $language_id), 'name', '');
    }

    /**
     * @return array{name: string, description: string, meta_title: string, meta_description: string, meta_keywords: string}
     */
    private function resolveProductMetaData(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        /** @var array{name: string, description: string, meta_title: string, meta_description: string, meta_keywords: string} $meta_data */
        $meta_data = [
            'name' => '',
            'description' => '',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
        ];

        if ($variant instanceof ProductVariant) {
            $variant_description = $variant->descriptions()
                ->where('language_id', $language_id)
                ->first();

            if ($variant_description !== null) {
                $meta_data['name'] = Str::trim((string)data_get($variant_description, 'name', ''));
                $meta_data['description'] = Str::trim((string)data_get($variant_description, 'description', ''));
                $meta_data['meta_title'] = Str::trim((string)data_get($variant_description, 'meta_title', ''));
                $meta_data['meta_description'] = Str::trim((string)data_get($variant_description, 'meta_description', ''));
                $meta_data['meta_keywords'] = Str::trim((string)data_get($variant_description, 'meta_keywords', ''));
            }
        }

        $product_description = $product->productDescription()
            ->where('language_id', $language_id)
            ->first();

        if ($product_description !== null) {
            $meta_data['description'] = filled($meta_data['description']) ? $meta_data['description'] : Str::trim((string)data_get($product_description, 'description', ''));
            $meta_data['meta_title'] = filled($meta_data['meta_title']) ? $meta_data['meta_title'] : Str::trim((string)data_get($product_description, 'meta_title', ''));
            $meta_data['meta_description'] = filled($meta_data['meta_description']) ? $meta_data['meta_description'] : Str::trim((string)data_get($product_description, 'meta_description', ''));
            $meta_data['meta_keywords'] = filled($meta_data['meta_keywords']) ? $meta_data['meta_keywords'] : Str::trim((string)data_get($product_description, 'meta_keywords', ''));

            $product_name = Str::trim((string)data_get($product_description, 'name', ''));
            $meta_data['name'] = filled($meta_data['name']) ? $meta_data['name'] : $product_name;
        }

        if (filled($meta_data['name'])) {
            return $meta_data;
        }

        $meta_data['name'] = Str::trim((string)$product->model);

        return $meta_data;
    }

    /**
     * @return array{price: float, rrc_price: float, is_discounted: bool}
     */
    private function resolveProductPriceData(Product $product, ?ProductVariant $variant): array
    {
        $rrc_price = $variant instanceof ProductVariant
            ? (float)$variant->price
            : (float)$product->price;
        $discount = $variant?->getLastActualAndLastModifiedDiscountForUserGroup(
            get_app_settings()?->user_group_id,
        );
        $discount_price = $discount instanceof ProductVariantDiscount
            ? (float)$discount->price
            : null;

        return [
            'price' => $discount_price ?? $rrc_price,
            'rrc_price' => $rrc_price,
            'is_discounted' => $discount_price !== null && $discount_price < $rrc_price,
        ];
    }

    private function resolveInStockState(?ProductVariant $variant, int $minimum_stock_quantity): bool
    {
        if (!$variant instanceof ProductVariant) {
            return false;
        }

        // Variant minimum is an item-level constraint; page setting minimum is a storefront policy.
        // We enforce the stricter one to keep stock behavior deterministic for customer pages.
        $minimum_quantity = max(0, (int)$variant->minimum, $minimum_stock_quantity);

        return $variant->is_active && (int)$variant->quantity >= $minimum_quantity;
    }

    /**
     * @return Collection<int, string>
     */
    private function resolveGalleryImages(Product $product, ?ProductVariant $variant): Collection
    {
        if ($variant instanceof ProductVariant) {
            $variant_image = $variant->image;
            $variant_images = $variant->images()
                ->orderBy('sort_order')
                ->pluck('image');
        } else {
            $variant_image = null;
            $variant_images = collect();
        }

        $variant_images = collect($variant_images)
            ->map(fn (mixed $image): string => $this->normalizeImagePath($image))
            ->filter(fn (string $image): bool => filled($image))
            ->values();

        if ($variant_images->isNotEmpty()) {
            if ($variant_image !== null) {
                $variant_images->prepend($variant_image);
            }

            return $variant_images;
        }

        $fallback_image = Str::trim((string)($variant_image ?? $product->image ?? ''));

        return filled($fallback_image)
            ? collect([$fallback_image])
            : collect();
    }

    private function normalizeImagePath(mixed $image): string
    {
        while (is_array($image)) {
            $image = Arr::get($image, 'image', Arr::first($image));
        }

        return Str::trim((string)$image);
    }

    /**
     * @param array<string, mixed> $page_settings_arr
     *
     * @return array{width:int,height:int}
     */
    private function resolveProductCustomerImageSize(array $page_settings_arr): array
    {
        return [
            'width' => max(
                1,
                (int)Arr::get(
                    $page_settings_arr,
                    'customer.images.product.width',
                    (int)Arr::get(
                        $page_settings_arr,
                        'images.product.width',
                        (int)config('app.page_settings.product.for_customer.image_width', (int)config('app.page_settings.product.image_width', 500)),
                    ),
                ),
            ),
            'height' => max(
                1,
                (int)Arr::get(
                    $page_settings_arr,
                    'customer.images.product.height',
                    (int)Arr::get(
                        $page_settings_arr,
                        'images.product.height',
                        (int)config('app.page_settings.product.for_customer.image_height', (int)config('app.page_settings.product.image_height', 500)),
                    ),
                ),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $page_settings_arr
     */
    private function resolveProductMinimumStockQuantity(array $page_settings_arr): int
    {
        return max(
            0,
            (int)Arr::get(
                $page_settings_arr,
                'customer.stock.minimum_stock_quantity',
                (int)Arr::get(
                    $page_settings_arr,
                    'stock.minimum_stock_quantity',
                    (int)config('app.page_settings.product.for_customer.minimum_stock_quantity', (int)config('app.page_settings.product.minimum_stock_quantity', 1)),
                ),
            ),
        );
    }

    /**
     * @param array{width:int,height:int} $image_size
     *
     * @return array{path:string,urls:array<string, string>,width:int,height:int}
     */
    private function buildImageData(string $image_path, array $image_size): array
    {
        $normalized_path = Str::trim($image_path);

        return [
            'path' => $normalized_path,
            'urls' => filled($normalized_path)
                ? multiple_convert_img_and_get_url(
                    $normalized_path,
                    (int)$image_size['width'],
                    (int)$image_size['height'],
                    is_square: false,
                    bg_color : 'transparent',
                )
                : [],
            'width' => (int)$image_size['width'],
            'height' => (int)$image_size['height'],
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     name: string,
     *     values: array<int, string>,
     *     value_links: array<int, array{value_id: int, value: string, url: string, is_selected: bool}>
     * }>
     */
    private function resolveOptionGroups(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        if (!$variant instanceof ProductVariant) {
            return [];
        }

        $product_slug = Str::trim((string)$product->getSlugByLanguageId($language_id));

        /** @var Collection<int, ProductVariant> $variants */
        $variants = ProductVariant::query()
            ->where('product_id', (int)$product->id)
            ->with([
                'attributeValues' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id)
                        ->orderBy('attribute_id');
                },
                'attributeValues.attribute.attributeDescription' => function ($query) use ($language_id): void {
                    $query->where('language_id', $language_id);
                },
            ])
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($variants->isEmpty()) {
            return [];
        }

        $variant_data = $this->buildVariantOptionData($variants);

        if ($variant_data === []) {
            return [];
        }

        $attribute_name_map = $variants
            ->flatMap(fn (ProductVariant $item): Collection => $item->attributeValues)
            ->mapWithKeys(function (ProductVariantAttributeValue $attribute_value): array {
                $attribute_id = (int)$attribute_value->attribute_id;
                $name = Str::trim((string)$attribute_value->attribute?->attributeDescription->first()?->name);

                if ($attribute_id < 1 || $name === '') {
                    return [];
                }

                return [$attribute_id => $name];
            })
            ->all();

        $selected_variant_data = collect($variant_data)
            ->first(fn (array $item): bool => (int)$item['variant_id'] === (int)$variant->id);

        if (!is_array($selected_variant_data)) {
            $selected_variant_data = collect($variant_data)->first();
        }

        if (!is_array($selected_variant_data)) {
            return [];
        }

        /** @var array<int, array{value_id:int,value:string,value_normalized:string}> $selected_attributes */
        $selected_attributes = $selected_variant_data['attributes'];
        $variant_id_order = collect($variant_data)
            ->pluck('variant_id')
            ->map(fn (mixed $variant_id): int => (int)$variant_id)
            ->values()
            ->all();
        $group_data_map = $this->buildGroupValuesData($variant_data, $attribute_name_map);

        return collect($group_data_map)
            ->sortKeys()
            ->map(function (
                array $group_data,
                int $attribute_id,
            ) use (
                $selected_attributes,
                $variant_data,
                $variant_id_order,
                $product_slug,
                $product
            ): array {
                $selected_value_normalized = (string)data_get($selected_attributes, "$attribute_id.value_normalized", '');
                $attribute_name = (string)$group_data['name'];
                $values_data = collect((array)$group_data['values'])
                    ->sortKeys()
                    ->all();

                return [
                    'key' => 'attribute_' . $attribute_id,
                    'name' => filled($attribute_name) ? $attribute_name : __('storefront/default.product.option_groups.attribute_fallback'),
                    'values' => collect($values_data)
                        ->pluck('value')
                        ->filter(fn (mixed $value): bool => filled((string)$value))
                        ->values()
                        ->all(),
                    'value_links' => collect($values_data)
                        ->map(function (
                            array $value_data,
                            string $value_normalized,
                        ) use (
                            $attribute_id,
                            $selected_value_normalized,
                            $selected_attributes,
                            $variant_data,
                            $variant_id_order,
                            $product_slug,
                            $product
                        ): array {
                            $target_variant_data = $this->resolveTargetVariantDataForOption(
                                variant_data              : $variant_data,
                                attribute_id              : $attribute_id,
                                candidate_value_normalized: $value_normalized,
                                selected_attributes       : $selected_attributes,
                                variant_id_order          : $variant_id_order,
                            );

                            $target_filters = $this->buildAttributeFiltersFromVariantData($target_variant_data);

                            return [
                                'value_id' => (int)$value_data['value_id'],
                                'value' => (string)$value_data['value'],
                                'url' => filled($product_slug) && $target_filters !== []
                                    ? localized_product_variant_route(
                                        product_slug     : $product_slug,
                                        product_id       : (int)$product->id,
                                        attribute_filters: $target_filters,
                                    )
                                    : '',
                                'is_selected' => $selected_value_normalized !== '' && $selected_value_normalized === $value_normalized,
                            ];
                        })
                        ->filter(fn (array $link): bool => filled((string)$link['value']))
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['value_links'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, ProductVariant> $variants
     *
     * @return array<int, array{
     *     variant_id:int,
     *     attributes:array<int, array{value_id:int,value:string,value_normalized:string}>
     * }>
     */
    private function buildVariantOptionData(Collection $variants): array
    {
        return $variants
            ->map(function (ProductVariant $item): array {
                $attributes = $item->attributeValues
                    ->mapWithKeys(function (ProductVariantAttributeValue $attribute_value): array {
                        $attribute_id = (int)$attribute_value->attribute_id;
                        $value = Str::trim((string)$attribute_value->value_string);
                        $value_normalized = Str::of($value)->lower()->toString();
                        $attribute_value_id = (int)$attribute_value->id;

                        if ($attribute_id < 1 || $attribute_value_id < 1 || $value_normalized === '') {
                            return [];
                        }

                        return [
                            $attribute_id => [
                                'value_id' => $attribute_value_id,
                                'value' => $value,
                                'value_normalized' => $value_normalized,
                            ],
                        ];
                    })
                    ->all();

                return [
                    'variant_id' => (int)$item->id,
                    'attributes' => $attributes,
                ];
            })
            ->filter(fn (array $item): bool => $item['attributes'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array{variant_id:int,attributes:array<int, array{value_id:int,value:string,value_normalized:string}>}> $variant_data
     * @param array<int, string>                                                                                                $attribute_name_map
     *
     * @return array<int, array{name:string,values:array<string, array{value_id:int,value:string}>}>
     */
    private function buildGroupValuesData(array $variant_data, array $attribute_name_map): array
    {
        $groups = [];

        foreach ($variant_data as $item) {
            foreach ($item['attributes'] as $attribute_id => $attribute_data) {
                $attribute_name = Str::trim((string)($attribute_name_map[$attribute_id] ?? ''));

                if (!isset($groups[$attribute_id])) {
                    $groups[$attribute_id] = [
                        'name' => $attribute_name,
                        'values' => [],
                    ];
                }

                if (blank((string)$groups[$attribute_id]['name']) && filled($attribute_name)) {
                    $groups[$attribute_id]['name'] = $attribute_name;
                }

                $value_key = (string)$attribute_data['value_normalized'];

                if ($value_key === '' || isset($groups[$attribute_id]['values'][$value_key])) {
                    continue;
                }

                $groups[$attribute_id]['values'][$value_key] = [
                    'value_id' => (int)$attribute_data['value_id'],
                    'value' => (string)$attribute_data['value'],
                ];
            }
        }

        return $groups;
    }

    /**
     * @param array<int, array{variant_id:int,attributes:array<int, array{value_id:int,value:string,value_normalized:string}>}> $variant_data
     * @param array<int, array{value_id:int,value:string,value_normalized:string}>                                              $selected_attributes
     * @param array<int, int>                                                                                                   $variant_id_order
     *
     * @return array{variant_id:int,attributes:array<int, array{value_id:int,value:string,value_normalized:string}>}|null
     */
    private function resolveTargetVariantDataForOption(
        array $variant_data,
        int $attribute_id,
        string $candidate_value_normalized,
        array $selected_attributes,
        array $variant_id_order,
    ): ?array {
        $candidates = collect($variant_data)
            ->filter(function (array $item) use ($attribute_id, $candidate_value_normalized): bool {
                $candidate_value = (string)data_get($item, "attributes.$attribute_id.value_normalized", '');

                return $candidate_value !== '' && $candidate_value === $candidate_value_normalized;
            })
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        /** @var Collection<int, array{
         *     variant_id:int,
         *     attributes:array<int, array{value_id:int,value:string,value_normalized:string}>,
         *     score:int,
         *     order:int
         * }> $scored
         */
        $scored = $candidates
            ->map(function (array $item) use ($selected_attributes, $attribute_id, $variant_id_order): array {
                $score = collect($selected_attributes)
                    ->reject(fn (array $selected_data): bool => (int)data_get($selected_data, 'value_id', 0) === $attribute_id)
                    ->reduce(function (int $carry, array $selected_data, int $selected_attribute_id) use ($item): int {
                        $selected_value = (string)$selected_data['value_normalized'];
                        $candidate_value = (string)data_get($item, "attributes.$selected_attribute_id.value_normalized", '');

                        if ($selected_value !== '' && $candidate_value !== '' && $selected_value === $candidate_value) {
                            return $carry + 1;
                        }

                        return $carry;
                    }, 0);

                $order = array_search((int)$item['variant_id'], $variant_id_order, true);

                return [
                    'variant_id' => (int)$item['variant_id'],
                    'attributes' => (array)$item['attributes'],
                    'score' => $score,
                    'order' => $order === false ? PHP_INT_MAX : (int)$order,
                ];
            })
            ->sortBy([
                ['score', 'desc'],
                ['order', 'asc'],
            ])
            ->values();

        $best = $scored->first();

        if (!is_array($best)) {
            return null;
        }

        return [
            'variant_id' => (int)$best['variant_id'],
            'attributes' => (array)$best['attributes'],
        ];
    }

    /**
     * @param array{variant_id:int,attributes:array<int, array{value_id:int,value:string,value_normalized:string}>}|null $variant_data
     *
     * @return array<int, array<int, int>>
     */
    private function buildAttributeFiltersFromVariantData(?array $variant_data): array
    {
        if (!is_array($variant_data)) {
            return [];
        }

        return collect((array)$variant_data['attributes'])
            ->mapWithKeys(function (array $attribute_data, int $attribute_id): array {
                $value_id = (int)$attribute_data['value_id'];

                if ($attribute_id < 1 || $value_id < 1) {
                    return [];
                }

                return [
                    $attribute_id => [$value_id],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, string>}>
     */
    private function resolveDetailsSections(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        $variant_translation = [];

        if ($variant instanceof ProductVariant) {
            $composition = $variant->compositions()
                ->where('language_id', $language_id)
                ->first();
            $care = $variant->cares()
                ->where('language_id', $language_id)
                ->first();

            $variant_translation = [
                'composition' => $composition === null ? null : [
                    'title' => $composition->title,
                    'items' => $composition->items ?? [],
                ],
                'care' => $care === null ? null : [
                    'title' => $care->title,
                    'items' => $care->items ?? [],
                ],
            ];
        }

        $product_composition = $product->compositions()
            ->where('language_id', $language_id)
            ->first();
        $product_care = $product->cares()
            ->where('language_id', $language_id)
            ->first();
        $product_translation = [
            'composition' => $product_composition === null ? null : [
                'title' => $product_composition->title,
                'items' => $product_composition->items ?? [],
            ],
            'care' => $product_care === null ? null : [
                'title' => $product_care->title,
                'items' => $product_care->items ?? [],
            ],
        ];

        $section_defaults = [
            'composition' => __('storefront/default.product.details.composition'),
            'care' => __('storefront/default.product.details.care'),
        ];

        return collect($section_defaults)
            ->map(function (string $default_label, string $section_key) use ($variant_translation, $product_translation): array {
                $variant_section = $this->normalizeDetailsSection(
                    section_data : Arr::get($variant_translation, $section_key),
                    default_label: $default_label,
                );
                $product_section = $this->normalizeDetailsSection(
                    section_data : Arr::get($product_translation, $section_key),
                    default_label: $default_label,
                );
                $resolved_section = $variant_section['items'] !== []
                    ? $variant_section
                    : $product_section;

                return [
                    'key' => $section_key,
                    'label' => (string)$resolved_section['label'],
                    'items' => (array)$resolved_section['items'],
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, items: array<int, string>}
     */
    private function normalizeDetailsSection(mixed $section_data, string $default_label): array
    {
        if (!is_array($section_data)) {
            return [
                'label' => $default_label,
                'items' => [],
            ];
        }

        $label = Str::trim((string)Arr::get($section_data, 'title', ''));

        if ($label === '') {
            $label = $default_label;
        }

        /** @var mixed $items_raw */
        $items_raw = Arr::get($section_data, 'items', []);

        $items = collect(is_array($items_raw) ? $items_raw : [])
            ->map(function (mixed $item): string {
                if (is_array($item)) {
                    return Str::trim((string)Arr::get($item, 'value', ''));
                }

                return Str::trim((string)$item);
            })
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        return [
            'label' => $label,
            'items' => $items,
        ];
    }
}
