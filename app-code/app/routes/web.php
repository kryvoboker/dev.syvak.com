<?php

declare(strict_types=1);

use App\Http\Controllers\Ajax\LiveSearchProductsAjaxController;
use App\Http\Controllers\Pages\CategoryController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\SearchProductsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

/*Route::get('/', function () {
    return redirect('/' . app()->getLocale());
})->middleware('localization.redirect');*/

Route::prefix('{locale}')
    ->name('localized.catalog.')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

        Route::get('/product/{slug}', function (string $locale, string $slug) {})->name('product.show');

        Route::get('/live-search', [LiveSearchProductsAjaxController::class, 'index'])->name('live-search-product-ajax.index');
        Route::get('/search', [SearchProductsController::class, 'index'])->name('search-products.index');
    });
