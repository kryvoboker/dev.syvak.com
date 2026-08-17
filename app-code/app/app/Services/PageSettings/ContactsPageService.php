<?php

declare(strict_types=1);

namespace App\Services\PageSettings;

use App\Models\PageSettings\PageSetting;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

final readonly class ContactsPageService
{
    public function __construct(
        private PageSettingsBootstrapService $page_settings_bootstrap_service,
    ) {
    }

    public function findBySlug(string $slug, int $language_id): ?PageSetting
    {
        return PageSetting::query()
            ->where('page_type', PageSetting::PAGE_TYPE_CONTACTS)
            ->whereHas('slugs', function (Builder $query) use ($slug, $language_id): void {
                $query
                    ->where('language_id', $language_id)
                    ->where('slug', $slug);
            })
            ->with('slugs')
            ->first();
    }

    public function getStaticPageSetting(): ?PageSetting
    {
        return PageSetting::query()
            ->where('page_type', PageSetting::PAGE_TYPE_CONTACTS)
            ->with('slugs')
            ->first();
    }

    /** @return array<string, mixed> */
    public function getViewData(PageSetting $page_setting, int $language_id): array
    {
        try {
            $settings = $this->getSettings($page_setting);
            $localized = $this->resolveLocalizedContent($settings, $language_id);

            return [
                'title' => $this->resolveString(Arr::get($localized, 'title'), __('storefront/contacts.fallbacks.title')),
                'working_hours' => [
                    'title' => $this->resolveString(Arr::get($localized, 'working_hours.title'), __('storefront/contacts.fallbacks.working_hours_title')),
                    'description' => $this->resolveNullableString(Arr::get($localized, 'working_hours.description')),
                    'content' => $this->resolveString(Arr::get($localized, 'working_hours.content'), ''),
                ],
                'phones' => $this->resolvePhones(Arr::get($settings, 'phones', [])),
                'emails' => $this->resolveEmails(Arr::get($settings, 'emails', [])),
                'addresses' => $this->resolveAddresses(Arr::get($settings, 'addresses', []), $language_id),
                'images' => $this->resolveImages(Arr::get($settings, 'images', [])),
                'map' => $this->resolveMap(Arr::get($settings, 'map', [])),
                'form' => Arr::get($settings, 'contact_form', []),
                'page_setting' => $page_setting,
            ];
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to prepare Contacts storefront data.', [
                'page_setting_id' => $page_setting->id,
                'language_id' => $language_id,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    /** @return array<string, mixed> */
    public function getSettings(PageSetting $page_setting): array
    {
        return $this->page_settings_bootstrap_service->normalizeContactsSettings(
            is_array($page_setting->settings) ? $page_setting->settings : [],
        );
    }

    /** @return array<string, array<int, mixed>> */
    public function getFormRules(PageSetting $page_setting): array
    {
        $settings = $this->getSettings($page_setting);
        $fields = Arr::get($settings, 'contact_form.fields', []);
        $fields = is_array($fields) ? $fields : [];
        $rules = [];

        foreach (['name', 'email', 'phone', 'text', 'file'] as $field_name) {
            $field = Arr::get($fields, $field_name, []);
            $field = is_array($field) ? $field : [];

            if (! (bool) Arr::get($field, 'enabled', false)) {
                $rules[$field_name] = ['prohibited'];

                continue;
            }

            $field_rules = [(bool) Arr::get($field, 'required', false) ? 'required' : 'nullable'];

            if ($field_name === 'file') {
                $allowed_types = array_values(array_filter((array) Arr::get($field, 'allowed_types', []), 'is_string'));
                $field_rules[] = Rule::file()
                    ->types($allowed_types)
                    ->max(max(1, (int) Arr::get($field, 'max_size_kb', 1)));
            } else {
                $field_rules[] = 'string';

                if ($field_name === 'email') {
                    $field_rules[] = 'email';
                }

                $this->appendStringLengthRules($field_rules, $field);
            }

            $regex = $this->resolveNullableString(Arr::get($field, 'regex'));

            if ($regex !== null) {
                $field_rules[] = function (string $attribute, mixed $value, Closure $fail) use ($regex): void {
                    if (preg_match($regex, (string) $value) !== 1) {
                        $fail(__('validation.regex', ['attribute' => $attribute]));
                    }
                };
            }

            $rules[$field_name] = $field_rules;
        }

        return $rules;
    }

    /** @param array<int, mixed> $field_rules @param array<string, mixed> $field */
    private function appendStringLengthRules(array &$field_rules, array $field): void
    {
        $min_length = Arr::get($field, 'min_length');
        $max_length = Arr::get($field, 'max_length');

        if (filled($min_length)) {
            $field_rules[] = 'min:' . max(0, (int) $min_length);
        }

        if (filled($max_length)) {
            $field_rules[] = 'max:' . max(0, (int) $max_length);
        }
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function resolveLocalizedContent(array $settings, int $language_id): array
    {
        $localized = Arr::get($settings, 'localized', []);
        $localized = is_array($localized) ? $localized : [];
        $content = Arr::get($localized, (string) $language_id);

        if (is_array($content)) {
            return $content;
        }

        $first_content = Arr::first($localized);

        return is_array($first_content) ? $first_content : [];
    }

    /** @param mixed $rows @return array<int, array{type: string, value: string}> */
    private function resolvePhones(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(fn (mixed $row): array => [
                'type' => $this->resolveString(data_get($row, 'type'), 'mobile'),
                'value' => $this->resolveString(data_get($row, 'value'), ''),
            ])
            ->filter(fn (array $phone): bool => filled($phone['value']))
            ->values()
            ->all();
    }

    /** @param mixed $rows @return array<int, string> */
    private function resolveEmails(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(fn (mixed $row): string => $this->resolveString(data_get($row, 'value'), ''))
            ->filter(fn (string $email): bool => filled($email))
            ->values()
            ->all();
    }

    /** @param mixed $rows @return array<int, array{title: string, description: ?string, value: string, url: ?string}> */
    private function resolveAddresses(mixed $rows, int $language_id): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(function (mixed $row) use ($language_id): array {
                $localized = data_get($row, 'localized.' . $language_id);

                if (! is_array($localized)) {
                    $localized = Arr::first((array) data_get($row, 'localized', []));
                }

                $localized = is_array($localized) ? $localized : [];

                return [
                    'title' => $this->resolveString(Arr::get($localized, 'title'), ''),
                    'description' => $this->resolveNullableString(Arr::get($localized, 'description')),
                    'value' => $this->resolveString(Arr::get($localized, 'value'), ''),
                    'url' => $this->resolvePublicUrl(Arr::get($localized, 'url')),
                ];
            })
            ->filter(fn (array $address): bool => filled($address['value']))
            ->values()
            ->all();
    }

    /** @param mixed $rows @return array<int, array<string, mixed>> */
    private function resolveImages(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled(data_get($row, 'path')))
            ->map(function (array $row): ?array {
                $path = Str::ltrim((string) Arr::get($row, 'path'), '/');

                if (! Storage::disk('public')->exists($path)) {
                    return null;
                }

                $width = max(1, (int) Arr::get($row, 'width', 600));
                $height = max(1, (int) Arr::get($row, 'height', 600));

                return [
                    'urls' => multiple_convert_img_and_get_url(
                        $path,
                        $width,
                        $height,
                        (bool) Arr::get($row, 'is_square', true),
                        (string) Arr::get($row, 'background', 'transparent'),
                    ),
                    'width' => $width,
                    'height' => $height,
                    'custom_css_classes' => Str::squish((string) Arr::get($row, 'custom_css_classes', '')),
                    'sort_order' => (int) Arr::get($row, 'sort_order', 0),
                ];
            })
            ->filter()
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /** @param mixed $map @return array<string, mixed> */
    private function resolveMap(mixed $map): array
    {
        $map = is_array($map) ? $map : [];
        $iframe_src = $this->resolveIframeSrc(Arr::get($map, 'iframe'));
        $latitude = Arr::get($map, 'latitude');
        $longitude = Arr::get($map, 'longitude');
        $coordinates = is_numeric($latitude) && is_numeric($longitude) ? $latitude . ',' . $longitude : null;

        return [
            'iframe_src' => $iframe_src,
            'coordinates' => $coordinates,
            'coordinates_url' => $coordinates === null ? null : 'https://www.google.com/maps/search/?api=1&query=' . urlencode((string) $coordinates),
            'width' => max(1, (int) Arr::get($map, 'width', 600)),
            'height' => max(1, (int) Arr::get($map, 'height', 400)),
            'custom_css_classes' => Str::squish((string) Arr::get($map, 'custom_css_classes', '')),
        ];
    }

    private function resolveIframeSrc(mixed $iframe): ?string
    {
        $iframe = Str::trim((string) $iframe);

        if ($iframe === '') {
            return null;
        }

        preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $iframe, $matches);
        $src = Str::trim((string) Arr::get($matches, 1, ''));

        return Str::startsWith($src, 'https://') ? $src : null;
    }

    private function resolvePublicUrl(mixed $url): ?string
    {
        $url = $this->resolveNullableString($url);

        return $url !== null && Str::startsWith($url, ['https://', 'http://', '/']) ? $url : null;
    }

    private function resolveString(mixed $value, string $fallback): string
    {
        $value = Str::trim((string) $value);

        return filled($value) ? $value : $fallback;
    }

    private function resolveNullableString(mixed $value): ?string
    {
        $value = Str::trim((string) $value);

        return filled($value) ? $value : null;
    }
}
