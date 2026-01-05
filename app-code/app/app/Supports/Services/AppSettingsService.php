<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\Settings\AppSetting;
use App\Models\Settings\Language;
use App\Models\Users\UserGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class AppSettingsService
{
    private ?AppSettingsData $app_settings_data = null;
    private const string CACHE_KEY = 'app.settings';
    private const int    TTL       = 3600; // 1 hour

    /**
     * @return AppSettingsData|null
     */
    public function getSettings(): ?AppSettingsData
    {
        $this->app_settings_data ??= Cache::get(self::CACHE_KEY);

        return $this->app_settings_data;
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function setSettings(): void
    {
        $this->removeSettings();

        $locale = app()->getLocale();

        if ($locale === null) {
            return;
        }

        $language_id = new Language()->getLanguageByCode($locale)?->id;

        throw_if(
            $language_id === null,
            message: "Language with code $locale not found!"
        );

        $user_group_id = Auth::user()?->user_group_id ?: new UserGroup()->getDefaultUserGroupId();

        throw_if(
            $user_group_id === null,
            message: 'The user group ID is not set!'
        );

        $this->app_settings_data = AppSettingsData::fromArray(array_merge(
            new AppSetting()->getAppSettings()?->toArray() ?? [],
            compact('language_id', 'user_group_id')
        ));

        Cache::remember(
            self::CACHE_KEY,
            self::TTL,
            fn() => $this->app_settings_data
        );
    }

    /**
     * @return void
     */
    public function removeSettings(): void
    {
        $this->app_settings_data = null;

        Cache::forget(self::CACHE_KEY);
    }
}
