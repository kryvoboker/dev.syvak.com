<?php

use Illuminate\Support\Facades\Route;
use LaravelLang\Routes\Facades\LocalizationRoute;

LocalizationRoute::group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
