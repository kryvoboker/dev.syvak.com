<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultLocalePrefix
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $allowed_locales           = get_allowed_locales();
        $path_info                 = Str::ltrim($request->getPathInfo(), '/');
        $is_livewire_request       = Str::startsWith($path_info, ['livewire-', 'livewire/']);
        $locale_key                = config('localization.locale_parameter');
        $fallback_locale           = $this->resolveFallbackLocale($allowed_locales);
        $session_locale            = session($locale_key);
        $normalized_session_locale = in_array($session_locale, $allowed_locales, true)
            ? $session_locale
            : $fallback_locale;
        $language_id               = resolve_language_by_locale($normalized_session_locale)?->id;

        if ($is_livewire_request) {
            session()->put($locale_key, $normalized_session_locale);
            app()->setLocale($normalized_session_locale);
            url()->defaults([$locale_key => $normalized_session_locale]);
            config(['app.locale' => $normalized_session_locale]);
            set_app_setting('language_id', $language_id);

            return $next($request);
        }

        $route                  = $request->route();
        $route_name             = $route?->getName();
        $route_locale           = $route?->parameter($locale_key);
        $has_locale_parameter   = in_array($locale_key, $route?->parameterNames() ?? [], true);
        $has_valid_route_locale = in_array($route_locale, $allowed_locales, true);

        if ($has_locale_parameter && !$has_valid_route_locale && filled($route_name)) {
            $route_parameters = array_merge($route->parameters(), [
                $locale_key => $normalized_session_locale,
            ]);

            return redirect()->route(
                $route_name,
                $route_parameters,
                Response::HTTP_TEMPORARY_REDIRECT,
            );
        }

        $resolved_locale = $has_valid_route_locale ? $route_locale : $normalized_session_locale;
        $language_id     = resolve_language_by_locale($resolved_locale)?->id;

        session()->put($locale_key, $resolved_locale);
        app()->setLocale($resolved_locale);
        url()->defaults([$locale_key => $resolved_locale]);
        config(['app.locale' => $resolved_locale]);
        set_app_setting('language_id', $language_id);

        return $next($request);
    }

    /**
     * @param array<int, string> $allowed_locales
     */
    private function resolveFallbackLocale(array $allowed_locales): string
    {
        $configured_locale = (string)config('app.locale', 'en');

        if (in_array($configured_locale, $allowed_locales, true)) {
            return $configured_locale;
        }

        return Arr::first($allowed_locales, default: 'en');
    }
}
