<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Cart\CartModeEnum;
use App\Filament\Auth\Http\Responses\LoginResponse;
use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Services\Cart\CartService;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Modules\ModuleCacheService;
use App\Services\Modules\ModuleDefinitionSyncService;
use App\Services\Modules\ModuleDiscoveryService;
use App\Services\Modules\ModuleInstanceService;
use App\Services\Modules\ModuleProviderRegistrarService;
use App\Services\Modules\ModuleProviderResolverService;
use App\Services\Modules\ModuleRuntimeResolverService;
use App\Services\Order\OrderAdminOptionsService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\CacheInvalidationService;
use App\Supports\Services\Currency\ConvertPrice;
use App\Supports\Services\GlobalConfigService;
use App\Supports\Services\Images\ImageUrlBuilderService;
use App\Supports\Services\RequestLookupContext;
use App\Supports\Services\StorefrontCacheService;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as LaravelView;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $new_storage_path = string_value(config('filesystems.new_storage_path'));

        if ($new_storage_path) {
            config([
                // Override compiled views path
                'view.compiled' => $new_storage_path . '/framework/views',
                'debugbar.storage.path' => $new_storage_path . '/debugbar',
                'logging.channels.single.path' => $new_storage_path . '/logs/laravel.log',
                'logging.channels.daily.path' => $new_storage_path . '/logs/laravel.log',
                'logging.channels.stack.path' => $new_storage_path . '/logs/laravel.log',
            ]);
        }

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->singleton(Language::class);
        $this->app->singleton(HeaderService::class);
        $this->app->singleton(FooterService::class);
        $this->app->singleton(ImageUrlBuilderService::class);
        $this->app->scoped(AppSettingsService::class);
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->scoped(CacheInvalidationService::class);
        $this->app->scoped(RequestLookupContext::class);
        $this->app->singleton(StorefrontCacheService::class);
        $this->app->singleton(ConvertPrice::class);
        $this->app->singleton(ModuleCacheService::class);
        $this->app->scoped(OrderAdminOptionsService::class);
        $this->app->singleton(ModuleDiscoveryService::class);
        $this->app->singleton(ModuleDefinitionSyncService::class);
        $this->app->singleton(ModuleInstanceService::class);
        $this->app->singleton(ModuleRuntimeResolverService::class);
        $this->app->singleton(ModuleProviderResolverService::class);
        $this->app->singleton(ModuleProviderRegistrarService::class);
        $this->app->singleton(PageSettingsBootstrapService::class);
        $this->app->singleton(GlobalConfigService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @throws MobileDetectException
     */
    public function boot(): void
    {
        RateLimiter::for('frontend-errors', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip() ?? 'unknown');
        });

        $new_storage_path = string_value(config('filesystems.new_storage_path'));
        $new_public_path = string_value(config('filesystems.new_public_path'));

        if ($new_storage_path && $new_public_path) {
            // Override symbolic links configuration
            config([
                'filesystems.links' => [
                    $new_public_path . '/storage' => $new_storage_path . '/app/public',
                ],
            ]);
        }

        require_once app_path('Supports/helpers.php');

        /**
         * Global locale parameter constraint prevents arbitrary values in
         * locale-aware routes and stabilizes URL matching for storefront/admin routes.
         */
        $allowed_locales = get_allowed_locales();
        $locale_key = string_value(config('localization.locale_parameter', 'locale'));

        if ($allowed_locales !== []) {
            $allowed_locales_pattern = implode('|', array_map('preg_quote', $allowed_locales));

            Route::pattern($locale_key, $allowed_locales_pattern);
        }

        // Register view namespaces for frontend (storefront) and admin
        // This allows usage like view('storefront::layouts.partials.header')
        $storefront_path = resource_path('views/storefront');

        if (File::isDirectory($storefront_path)) {
            View::addNamespace('storefront', $storefront_path);
        }

        $default_no_image_path = string_value(config('app.images.default_no_image', 'images/no-image.png'));
        $app_settings_service = null;
        $device_type = string_value(config('devices.types.desktop'));
        $max_viewport_width = integer_value(config('app.frontend.max_viewport_width', 1920));

        if (! $this->app->runningUnitTests()) {
            $currency = (new Currency())->getDefaultActiveCurrency();
            $app_settings_service = app(AppSettingsService::class);
            $app_settings_service->setSettings();
            $detect = new MobileDetect();

            if ($detect->isMobile()) {
                $device_type = $detect->isTablet()
                    ? string_value(config('devices.types.tablet'))
                    : string_value(config('devices.types.mobile'));
            }

            $max_viewport_width = max(
                1,
                integer_value(data_get(
                    $app_settings_service->getSettings(),
                    'system_settings.frontend.max_viewport_width',
                    integer_value(config('app.frontend.max_viewport_width', 1920)),
                )),
            );
            $default_no_image_path = string_value(data_get(
                $app_settings_service->getSettings(),
                'system_settings.images.default_no_image',
                string_value(config('app.images.default_no_image', 'images/no-image.png')),
            ));

            if ($currency !== null) {
                config([
                    'app.currency.current_currency_code' => $currency->code,
                    'app.currency.current_currency_symbol' => $currency->symbol_left ?: $currency->symbol_right,
                    'app.currency.current_currency_exchange_rate' => $currency->exchange_rate,
                    'app.currency.current_exchange_rate' => $currency->exchange_rate,
                    'app.currency.current_format_locale' => $currency->format_locale,
                    'app.currency.current_decimal_places' => $currency->decimal_places,
                    'devices.current_device_type' => $device_type,
                ]);

                app(ConvertPrice::class)->setDefaultCurrency($currency);
            }

            $app_settings_timezone = $app_settings_service->getSettings()?->timezone;

            if (filled($app_settings_timezone) && in_array($app_settings_timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $app_settings_timezone]);

                date_default_timezone_set($app_settings_timezone);
            }
        }

        View::share([
            'app_settings' => $app_settings_service?->getSettings(),
            'no_image_url' => asset('storage/' . $default_no_image_path),
            'max_viewport_width' => $max_viewport_width,
            'current_device_type' => $device_type,
            'locale_key' => $locale_key,
        ]);

        /**
         * NOTE:
         * Locale is finalized by SetDefaultLocalePrefix middleware.
         * We must inject current locale at render time, otherwise early
         * boot-time share can keep default locale (e.g. "en") for all views.
         */
        View::composer('*', function (LaravelView $view): void {
            $view->with('current_locale', app()->getLocale());
        });

        View::composer('storefront.layouts.partials.header', function (LaravelView $view): void {
            $view->with(
                'cart_total_products',
                app(CartService::class)->getTotalProducts(CartModeEnum::Regular->value),
            );
        });
    }
}
