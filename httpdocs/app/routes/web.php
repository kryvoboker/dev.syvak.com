<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

/*Route::get('/', function () {
    return redirect('/' . app()->getLocale());
})->middleware('localization.redirect');*/

Route::prefix('{locale}')
    ->name('localized.catalog.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Pages\HomeController::class, 'index'])->name('home');

        Route::get('/category/{slug}', function (string $locale, string $slug) {

        })->name('category.show');
    });
