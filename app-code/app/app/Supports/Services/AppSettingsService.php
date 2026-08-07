<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Data\AppSettingsData;
use App\Models\ApplicationSettings\AppSetting;
use App\Models\Users\UserGroup;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $contacts_settings = app(PageSettingsBootstrapService::class)->getContactsSettings();
        $global_configs = app(GlobalConfigService::class)->getActiveGlobalConfigs();

        $this->app_settings_data = AppSettingsData::fromArray(array_merge(
            $app_settings,
            $this->resolveContactsRuntimeSettings($contacts_settings, $locale, $language_id),
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
        if ($this->app_settings_data instanceof AppSettingsData && property_exists($this->app_settings_data, $key)) {
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

    /**
     * @param array<string, mixed> $contacts_settings
     * @return array<string, mixed>
     */
    private function resolveContactsRuntimeSettings(array $contacts_settings, string $locale, ?int $language_id): array
    {
        $localized_settings = Arr::get($contacts_settings, 'localized', []);
        $localized_settings = is_array($localized_settings) ? $localized_settings : [];
        $localized_content = is_array(Arr::get($localized_settings, (string) $language_id))
            ? Arr::get($localized_settings, (string) $language_id)
            : [];

        $working_hours_content = (string) Arr::get($localized_content, 'working_hours.content', '');
        $work_time = $working_hours_content === '' ? [] : [$locale => $working_hours_content];

        $coordinates = null;
        $latitude = Arr::get($contacts_settings, 'map.latitude');
        $longitude = Arr::get($contacts_settings, 'map.longitude');

        if (is_numeric($latitude) && is_numeric($longitude)) {
            $coordinates = Str::replace(' ', '', "$latitude,$longitude");
        }

        return [
            'contact_emails' => Arr::get($contacts_settings, 'emails', []),
            'contact_phones' => Arr::get($contacts_settings, 'phones', []),
            'work_time' => $work_time,
            'contact_addresses' => Arr::get($contacts_settings, 'addresses', []),
            'coordinates' => $coordinates,
            'iframe_map' => $this->resolveNullableString(Arr::get($contacts_settings, 'map.iframe')),
        ];
    }

    private function resolveNullableString(mixed $value): ?string
    {
        $value = Str::trim((string) $value);

        return filled($value) ? $value : null;
    }
}
