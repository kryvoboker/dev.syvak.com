<?php

declare(strict_types=1);

namespace App\Http\Middleware\User;

use App\Models\Settings\Currency;
use App\Services\AppSettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCommonPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currency             = new Currency()->getDefaultActiveCurrency();
        $app_settings_service = app(AppSettingsService::class);

        $app_settings_service->setSettings();

        View::share([
            'app_settings'   => $app_settings_service->getSettings(),
            'no_image_url'   => asset('storage/' . config('app.images.default_no_image')),
            'current_locale' => app()->getLocale(),
        ]);

        if ($currency !== null) {
            config([
                'app.currency.default_currency_code'          => $currency->code,
                'app.currency.default_currency_symbol'        => $currency->symbol_left ?: $currency->symbol_right,
                'app.currency.default_currency_exchange_rate' => $currency->exchange_rate,
                'app.currency.default_format_locale'          => $currency->format_locale,
                'app.currency.default_decimal_places'         => $currency->decimal_places,
            ]);
        }

        if (!empty($app_settings_service->timezone) && in_array($app_settings_service->timezone, timezone_identifiers_list())) {
            config(['app.timezone' => $app_settings_service->timezone]);

            date_default_timezone_set($app_settings_service->timezone);
        }

        return $next($request);
    }
}
