<?php

declare(strict_types=1);

namespace App\Http\Middleware\User;

use App\Models\ApplicationSettings\Currency;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\Currency\ConvertPrice;
use Closure;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCommonPreferences
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     *
     * @throws MobileDetectException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currency             = new Currency()->getDefaultActiveCurrency();
        $app_settings_service = app(AppSettingsService::class);
        $app_settings_service->setSettings();
        $detect = new MobileDetect();

        if ($detect->isMobile()) {
            $device_type = $detect->isTablet() ? config('devices.types.tablet') : config('devices.types.mobile');
        } else {
            $device_type = config('devices.types.desktop');
        }

        $max_viewport_width = max(
            1,
            (int) data_get(
                $app_settings_service->getSettings(),
                'system_settings.frontend.max_viewport_width',
                (int) config('app.frontend.max_viewport_width', 1920),
            ),
        );
        $default_no_image_path = (string) data_get(
            $app_settings_service->getSettings(),
            'system_settings.images.default_no_image',
            (string) config('app.images.default_no_image', 'images/no-image.png'),
        );

        View::share([
            'app_settings'        => $app_settings_service->getSettings(),
            'no_image_url'        => asset('storage/' . $default_no_image_path),
            'current_locale'      => app()->getLocale(),
            'max_viewport_width'  => $max_viewport_width,
            'current_device_type' => $device_type,
        ]);

        if ($currency !== null) {
            config([
                'app.currency.current_currency_code'          => $currency->code,
                'app.currency.current_currency_symbol'        => $currency->symbol_left ?: $currency->symbol_right,
                'app.currency.current_currency_exchange_rate' => $currency->exchange_rate,
                'app.currency.current_format_locale'          => $currency->format_locale,
                'app.currency.current_decimal_places'         => $currency->decimal_places,
                'devices.current_device_type'                 => $device_type,
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
