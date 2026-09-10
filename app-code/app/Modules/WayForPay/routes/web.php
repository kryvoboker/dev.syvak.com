<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\WayForPay\Http\Controllers\WayForPayCallbackController;
use Modules\WayForPay\Http\Controllers\WayForPayReturnController;

$locale_key_value = config('localization.locale_parameter', 'locale');
$locale_key = is_scalar($locale_key_value) ? (string) $locale_key_value : 'locale';
$allowed_locales = get_allowed_locales();

Route::prefix('{' . $locale_key . '}')
    ->whereIn($locale_key, $allowed_locales)
    ->name('localized.catalog.')
    ->group(function (): void {
        Route::post('/wayforpay/callback', WayForPayCallbackController::class)
            ->withoutMiddleware(PreventRequestForgery::class)
            ->name('wayforpay.callback');

        Route::match(['get', 'post'], '/wayforpay/return', WayForPayReturnController::class)
            ->withoutMiddleware(PreventRequestForgery::class)
            ->name('wayforpay.return');
    });
