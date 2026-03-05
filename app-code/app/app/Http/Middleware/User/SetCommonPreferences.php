<?php

declare(strict_types=1);

namespace App\Http\Middleware\User;

use App\Models\Settings\Currency;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCommonPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currency             = new Currency()->getDefaultActiveCurrency();
        $app_settings_service = app(AppSettingsService::class);

        $app_settings_service->setSettings();

        View::share([
            'app_settings'       => $app_settings_service->getSettings(),
            'no_image_url'       => asset('storage/' . config('app.images.default_no_image')),
            'current_locale'     => app()->getLocale(),
            'max_viewport_width' => (int) config('app.frontend.max_viewport_width'),
        ]);

        if ($currency !== null) {
            config([
                'app.currency.default_currency_code'          => $currency->code,
                'app.currency.default_currency_symbol'        => $currency->symbol_left ?: $currency->symbol_right,
                'app.currency.default_currency_exchange_rate' => $currency->exchange_rate,
                'app.currency.default_format_locale'          => $currency->format_locale,
                'app.currency.default_decimal_places'         => $currency->decimal_places,
            ]);

            app(ConvertPrice::class)->setDefaultCurrency($currency);
        }

        $app_settings_timezone = $app_settings_service->getSettings()?->timezone;

        if (filled($app_settings_timezone) && in_array($app_settings_timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $app_settings_timezone]);

            date_default_timezone_set($app_settings_timezone);
        }

        return $next($request);
    }
}
