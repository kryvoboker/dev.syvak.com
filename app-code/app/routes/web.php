<?php

declare(strict_types=1);

use App\Http\Controllers\Ajax\CatalogFilterAjaxController;
use App\Http\Controllers\Ajax\LiveSearchProductsAjaxController;
use App\Http\Controllers\Pages\CategoryController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\SearchProductsController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

Route::get('/alyo-admin', function (): RedirectResponse {
    return redirect('/' . app()->getLocale() . '/alyo-admin');
});

Route::get('/alyo-admin/login', function (): RedirectResponse {
    return redirect('/' . app()->getLocale() . '/alyo-admin/login');
});

$locale_key = config('localization.locale_parameter', 'locale');

Route::prefix('{' . $locale_key . '}')
    ->whereIn($locale_key, (array)config('app.locales', [config('app.locale', 'en')]))
    ->name('localized.catalog.')
    ->group(function (): void {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
        Route::get('/category/{slug}/filters', [CatalogFilterAjaxController::class, 'index'])->name('catalog-filter-ajax.index');

        Route::get('/product/{slug}', function (string $locale, string $slug): void {})->name('product.show');

        Route::get('/live-search', [LiveSearchProductsAjaxController::class, 'index'])->name('live-search-product-ajax.index');
        Route::get('/search', [SearchProductsController::class, 'index'])->name('search-products.index');
    });
