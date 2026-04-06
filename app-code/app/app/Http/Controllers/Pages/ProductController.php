<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Products\Product;
use App\Models\Catalogs\Products\ProductVariant;
use Illuminate\Contracts\View\View;
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

        /** @var view-string $view_name */
        $view_name = 'catalog.pages.product';

        return view($view_name, [
            'product' => $product,
            'variant' => $variant,
        ]);
    }
}
