<?php

declare(strict_types=1);

namespace App\Http\Middleware\User;

use App\Models\Settings\AppSetting;
use App\Models\Settings\Currency;
use Closure;
use Illuminate\Http\Request;
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
        $currency     = new Currency()->getDefaultActiveCurrency();
        $app_settings = get_app_settings();

        if ($currency !== null) {
            config([
                'app.currency.default_currency_code'          => $currency->code,
                'app.currency.default_currency_symbol'        => $currency->symbol_left ?: $currency->symbol_right,
                'app.currency.default_currency_exchange_rate' => $currency->exchange_rate,
                'app.currency.default_format_locale'          => $currency->format_locale,
                'app.currency.default_decimal_places'         => $currency->decimal_places,
            ]);
        }

        if ($app_settings !== null && !empty($app_settings->timezone) && in_array($app_settings->timezone, timezone_identifiers_list())) {
            config(['app.timezone' => $app_settings->timezone]);

            date_default_timezone_set($app_settings->timezone);
        }

        return $next($request);
    }
}
