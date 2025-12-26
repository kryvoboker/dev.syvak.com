<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\Settings\AppSetting;
use App\Models\Settings\Language;
use App\Models\Users\UserGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class AppSettingsService
{
    private const string CACHE_KEY = 'app.settings';
    private const int    TTL       = 3600; // 1 hour

    /**
     * @return AppSettingsData|null
     */
    public function getSettings(): ?AppSettingsData
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * @return void
     */
    public function setSettings(): void
    {
        $this->removeSettings();

        $locale = app()->getLocale();

        if ($locale === null) {
            return;
        }

        Cache::remember(
            self::CACHE_KEY,
            self::TTL,
            function () use ($locale) {
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

                return AppSettingsData::fromArray(array_merge(
                    new AppSetting()->getAppSettings()?->toArray() ?? [],
                    compact('language_id', 'user_group_id')
                ));
            });
    }

    /**
     * @return void
     */
    public function removeSettings(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
