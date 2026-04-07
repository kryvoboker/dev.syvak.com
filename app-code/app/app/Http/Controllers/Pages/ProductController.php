<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Catalogs\Products\ProductVariantAttributeValue;
use App\Services\FooterService;
use App\Services\HeaderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function show(string $locale, string $slug, ?string $variant_slug = null): View
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

        $variant = null;

        if (filled($variant_slug)) {
            $variant = ProductVariant::findBySlug((string) $variant_slug, (int) $language->id);

            if ($variant instanceof ProductVariant && (int) $variant->product_id !== (int) $product->id) {
                throw new NotFoundHttpException();
            }
        }

        if (! $variant instanceof ProductVariant) {
            $variant = $product->defaultVariant;
        }

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
            'product_view_data' => $this->buildProductViewData($product, $variant, (int) $language->id),
            'product'           => $product,
            'variant'           => $variant,
        ]);
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    private function resolveBreadcrumbs(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        $product_title = $this->resolveProductTitle($product, $variant, $language_id);

        return [
            breadcrumb(__('catalog/default.links.home'), localizedRoute('catalog.home')),
            breadcrumb($product_title),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProductViewData(Product $product, ?ProductVariant $variant, int $language_id): array
    {
        $product_title   = $this->resolveProductTitle($product, $variant, $language_id);
        $product_sku     = (string) $product->sku;
        $product_price   = $this->resolveProductPrice($product, $variant);
        $formatted_price = format_price(
            $product_price,
            config('app.currency.current_currency_code'),
            (float) config('app.currency.current_exchange_rate'),
        );

        $is_in_stock      = $this->resolveInStockState($variant);
        $gallery_images   = $this->resolveGalleryImages($product, $variant);
        $main_image_path  = (string) ($gallery_images->first() ?? '');
        $option_groups    = $this->resolveOptionGroups($variant, $language_id);
        $details_sections = $this->resolveDetailsSections();

        return [
            'title'            => $product_title,
            'sku'              => $product_sku,
            'price_formatted'  => $formatted_price,
            'is_in_stock'      => $is_in_stock,
            'main_image_path'  => $main_image_path,
            'gallery_images'   => $gallery_images,
            'option_groups'    => $option_groups,
            'details_sections' => $details_sections,
            'labels'           => $this->resolveUiLabels(),
        ];
    }

    private function resolveProductTitle(Product $product, ?ProductVariant $variant, int $language_id): string
    {
        $variant_title = $variant?->descriptions()
            ->where('language_id', $language_id)
            ->value('name');

        if (filled((string) $variant_title)) {
            return trim((string) $variant_title);
        }

        $product_title = $product->productDescription()
            ->where('language_id', $language_id)
            ->value('name');

        if (filled((string) $product_title)) {
            return trim((string) $product_title);
        }

        return trim((string) $product->model);
    }

    private function resolveProductPrice(Product $product, ?ProductVariant $variant): float
    {
        if ($variant instanceof ProductVariant && is_numeric($variant->price)) {
            return (float) $variant->price;
        }

        return is_numeric($product->price) ? (float) $product->price : 0.0;
    }

    private function resolveInStockState(?ProductVariant $variant): bool
    {
        if (! $variant instanceof ProductVariant) {
            return false;
        }

        $minimum_quantity = max(0, (int) $variant->minimum);

        return (bool) $variant->is_active && (int) $variant->quantity >= $minimum_quantity;
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
        $fallback_image = trim((string) ($variant_image ?? $product->image ?? ''));

        return filled($fallback_image)
            ? collect([$fallback_image])
            : collect();
    }

    private function normalizeImagePath(mixed $image): string
    {
        while (is_array($image)) {
            $image = Arr::get($image, 'image', Arr::first($image));
        }

        return trim((string) $image);
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
