<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/' . app()->getLocale());

/*Route::get('/', function () {
    return redirect('/' . app()->getLocale());
})->middleware('localization.redirect');*/

Route::prefix('{locale}')
    ->group(function () {
        Route::get('/', function () {
            return view('welcome');
        })->name('home');
    });

/*Route::get('/{locale}/category/{slug}', function (string $locale, string $slug) {
    $language_id = Language::where('code', $locale)->value('id');
    $category = Category::findBySlug($slug, $language_id);
    // ...
});*/
