<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\Settings\AppSetting;
use App\Models\Settings\Language;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

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

                if ($language_id === null) {
                    throw new RuntimeException('Current language not found!');
                }

                return AppSettingsData::from(array_merge(
                    new AppSetting()->getAppSettings()?->toArray() ?? [],
                    compact('language_id')
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
