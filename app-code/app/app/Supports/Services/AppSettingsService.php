<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\AppSetting;
use App\Models\Users\UserGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class AppSettingsService
{
    private ?AppSettingsData $app_settings_data = null;

    public function getSettings(): ?AppSettingsData
    {
        return $this->app_settings_data;
    }

    public function setSettings(): void
    {
        $locale = app()->getLocale();

        if ($locale === null) {
            return;
        }

        $language_id = $this->resolveLanguageId($locale);
        $user = Auth::user();
        $user_group_id = $user?->user_group_id;

        if ($user_group_id === null) {
            $user_group_id = (new UserGroup())->getDefaultUserGroupId();
        }

        if ($user_group_id === null) {
            Log::channel('stack')->warning('Unable to resolve default user group ID while building app settings.', [
                'locale' => $locale,
            ]);
        }

        $app_setting = (new AppSetting())->getAppSettings();
        $app_settings = $app_setting !== null ? $app_setting->toArray() : [];
        $global_configs = app(GlobalConfigService::class)->getActiveGlobalConfigs();

        $this->app_settings_data = AppSettingsData::fromArray(array_merge(
            $app_settings,
            ['global_configs' => $global_configs],
            compact('language_id', 'user_group_id'),
        ));
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setSetting(string $key, mixed $value): void
    {
        if (property_exists($this->app_settings_data, $key)) {
            $this->app_settings_data->$key = $value;
        }
    }

    private function resolveLanguageId(string $locale): ?int
    {
        $lookup_context = app(RequestLookupContext::class);
        $language = $lookup_context->getLanguageByCode($locale);

        if ($language !== null) {
            $language_id = $language->id;
        } else {
            $default_language = $lookup_context->getDefaultLanguage();
            $language_id = $default_language?->id;
        }

        $lookup_context->forgetLanguageByCode();

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
    }
}
