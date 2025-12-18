<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AppSettingsData;
use App\Models\Settings\AppSetting;
use Illuminate\Support\Facades\Cache;

final class AppSettingsService
{
    private const string CACHE_KEY = 'app.settings';
    private const int    TTL       = 3600; // 1 hour

    /**
     * @return AppSetting|null
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
        Cache::remember(
            self::CACHE_KEY,
            self::TTL,
            function () {
                return AppSettingsData::from(new AppSetting()->getAppSettings() ?? []);
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
