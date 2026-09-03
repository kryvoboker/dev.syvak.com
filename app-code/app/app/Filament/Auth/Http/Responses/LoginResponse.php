<?php

declare(strict_types=1);

namespace App\Filament\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param $request
     *
     * @throws NoDefaultPanelSetException
     * @return RedirectResponse
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toResponse($request): RedirectResponse
    {
        $locale = $this->resolveLocale();
        $panel = Filament::getCurrentOrDefaultPanel();
        $dashboard_url = Dashboard::getUrl(
            parameters: ['locale' => $locale],
            panel: $panel?->getId(),
        );

        return redirect()->intended($dashboard_url);
    }

    /**
     * @return string
     */
    private function resolveLocale(): string
    {
        $allowed_locales = get_allowed_locales();
        $locale_key = string_value(config('localization.locale_parameter', 'locale'));
        $session_locale = session($locale_key);

        if (is_string($session_locale) && in_array($session_locale, $allowed_locales, true)) {
            return $session_locale;
        }

        $application_locale = app()->getLocale();

        if (in_array($application_locale, $allowed_locales, true)) {
            return $application_locale;
        }

        $fallback_locale = Arr::first($allowed_locales, default: 'en');

        Log::channel('stack')->warning('[FIX:admin-locale] Could not resolve the selected admin locale.', [
            'session_locale' => $session_locale,
            'application_locale' => $application_locale,
            'fallback_locale' => $fallback_locale,
        ]);

        return $fallback_locale;
    }
}
