<?php

use Illuminate\Support\Facades\Route;
use LaravelLang\Routes\Facades\LocalizationRoute;

LocalizationRoute::group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});

/*Route::get('/{locale}/category/{slug}', function (string $locale, string $slug) {
    $language_id = Language::where('code', $locale)->value('id');
    $category = Category::findBySlug($slug, $language_id);
    // ...
});*/
