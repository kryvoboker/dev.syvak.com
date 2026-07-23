<?php

declare(strict_types=1);

namespace App\Filament\Resources\Trait;

use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;

trait StorefrontProductLinkTrait
{
    protected static function getStorefrontProductUrl(Product $product, ?int $language_id = null): ?string
    {
        $product_slug = self::getLocalizedSlug($product, $language_id);

        if ($product_slug === null) {
            return null;
        }

        return localized_route('localized.catalog.product.show', ['slug' => $product_slug]);
    }

    protected static function getStorefrontVariantUrl(ProductVariant $variant, ?int $language_id = null): ?string
    {
        $product = $variant->product;
        $product_slug = self::getLocalizedSlug($product, $language_id);
        $variant_slug = self::getLocalizedSlug($variant, $language_id);

        if ($product_slug === null) {
            return null;
        }

        if ($variant_slug === null) {
            return localized_route('localized.catalog.product.show', ['slug' => $product_slug]);
        }

        return localized_route('localized.catalog.product.variant.show', [
            'slug' => $product_slug,
            'variant_slug' => $variant_slug,
        ]);
    }

    private static function getLocalizedSlug(Product|ProductVariant $sluggable, ?int $language_id): ?string
    {
        $slug = $sluggable->slugs
            ->firstWhere('language_id', $language_id)?->slug;

        if ($slug === null) {
            $slug = $sluggable->slugs->first()?->slug;
        }

        return filled($slug) ? (string) $slug : null;
    }
}
