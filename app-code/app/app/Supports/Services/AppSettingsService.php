<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\AppSetting;
use App\Models\ApplicationSettings\Language;
use App\Models\Users\UserGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class AppSettingsService
{
    private ?AppSettingsData $app_settings_data = null;

    private const string CACHE_KEY = 'app.settings';

    private const int    TTL = 3600; // 1 hour

    public function getSettings(): ?AppSettingsData
    {
        $this->app_settings_data ??= Cache::get(self::CACHE_KEY);

        return $this->app_settings_data;
    }

    public function setSettings(): void
    {
        $this->removeSettings();

        $locale = app()->getLocale();

        if ($locale === null) {
            return;
        }

        $language_id   = $this->resolveLanguageId($locale);
        $user_group_id = Auth::user()?->user_group_id ?: new UserGroup()->getDefaultUserGroupId();

        if ($user_group_id === null) {
            Log::channel('stack')->warning('Unable to resolve default user group ID while building app settings.', [
                'locale' => $locale,
            ]);
        }

        $this->app_settings_data = AppSettingsData::fromArray(array_merge(
            new AppSetting()->getAppSettings()?->toArray() ?? [],
            compact('language_id', 'user_group_id'),
        ));

        Cache::remember(
            self::CACHE_KEY,
            self::TTL,
            fn () => $this->app_settings_data,
        );
    }

    private function resolveLanguageId(string $locale): ?int
    {
        $language_model = new Language();
        $language_id    = $language_model->getLanguageByCode($locale)?->id
            ?: $language_model->getDefaultLanguage()?->id
            ?: Language::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->value('id')
            ?: Language::query()->value('id');

        if ($language_id === null) {
            Log::channel('stack')->warning('Unable to resolve language ID while building app settings.', [
                'locale' => $locale,
            ]);
        }

        return $language_id;
    }

    public function removeSettings(): void
    {
        $this->app_settings_data = null;

        Cache::forget(self::CACHE_KEY);
    }
}
