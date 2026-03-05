<?php

declare(strict_types=1);

use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\SearchProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

/*Route::get('/', function () {
    return redirect('/' . app()->getLocale());
})->middleware('localization.redirect');*/

Route::prefix('{locale}')
    ->name('localized.catalog.')
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::get('/category/{slug}', function (string $locale, string $slug) {})->name('category.show');

        Route::get('/product/{slug}', function (string $locale, string $slug) {})->name('product.show');

        Route::get('/search', [SearchProductController::class, 'index'])->name('search.index');
        Route::get('/search/show', [SearchProductController::class, 'show'])->name('search.show');
    });
