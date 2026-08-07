<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class () extends Migration {
    public function up(): void
    {
        try {
            DB::transaction(function (): void {
                $legacy_app_settings = DB::table('app_settings')->first();
                $contacts_page_setting = DB::table('page_settings')
                    ->where('page_type', 'contacts')
                    ->first();

                $contacts_settings = $this->decodeJson($contacts_page_setting?->settings);
                $legacy_data = $legacy_app_settings === null
                    ? []
                    : [
                        'contact_emails' => $this->decodeJson($legacy_app_settings->contact_emails),
                        'contact_phones' => $this->decodeJson($legacy_app_settings->contact_phones),
                        'work_time' => $this->decodeJson($legacy_app_settings->work_time),
                        'contact_addresses' => $this->decodeJson($legacy_app_settings->contact_addresses),
                        'coordinates' => $legacy_app_settings->coordinates,
                        'iframe_map' => $legacy_app_settings->iframe_map,
                    ];

                $contacts_settings = $this->mergeLegacyContactData($contacts_settings, $legacy_data);

                if ($contacts_page_setting === null) {
                    DB::table('page_settings')->insert([
                        'page_type' => 'contacts',
                        'settings' => json_encode($contacts_settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('page_settings')
                        ->where('id', $contacts_page_setting->id)
                        ->update([
                            'settings' => json_encode($contacts_settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                }

                Schema::table('app_settings', function (Blueprint $table): void {
                    $table->dropColumn([
                        'contact_emails',
                        'contact_phones',
                        'work_time',
                        'contact_addresses',
                        'coordinates',
                        'iframe_map',
                    ]);
                });
            });
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Failed to migrate legacy contact settings.', [
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table): void {
            $table->string('contact_emails', 500)->nullable();
            $table->string('contact_phones', 500)->nullable();
            $table->string('work_time')->nullable();
            $table->json('contact_addresses')->nullable();
            $table->string('coordinates')->nullable();
            $table->text('iframe_map')->nullable();
        });

        $contacts_page_setting = DB::table('page_settings')
            ->where('page_type', 'contacts')
            ->first();
        $app_settings = DB::table('app_settings')->first();

        if ($contacts_page_setting === null || $app_settings === null) {
            return;
        }

        $contacts_settings = $this->decodeJson($contacts_page_setting->settings);
        $languages = DB::table('languages')->pluck('code', 'id')->all();

        DB::table('app_settings')
            ->where('id', $app_settings->id)
            ->update([
                'contact_emails' => json_encode($this->getLegacyLocalizedValues($contacts_settings, 'emails', $languages), JSON_UNESCAPED_UNICODE),
                'contact_phones' => json_encode($this->getLegacyLocalizedValues($contacts_settings, 'phones', $languages), JSON_UNESCAPED_UNICODE),
                'work_time' => json_encode($this->getLegacyWorkingHours($contacts_settings, $languages), JSON_UNESCAPED_UNICODE),
                'contact_addresses' => json_encode($this->getLegacyAddresses($contacts_settings, $languages), JSON_UNESCAPED_UNICODE),
                'coordinates' => $this->getLegacyCoordinates($contacts_settings),
                'iframe_map' => (string) Arr::get($contacts_settings, 'map.iframe', ''),
            ]);
    }

    /**
     * @param array<string, mixed> $contacts_settings
     * @param array<string, mixed> $legacy_data
     * @return array<string, mixed>
     */
    private function mergeLegacyContactData(array $contacts_settings, array $legacy_data): array
    {
        if (blank(Arr::get($contacts_settings, 'emails'))) {
            $contacts_settings['emails'] = $this->makeValueRows(Arr::get($legacy_data, 'contact_emails'));
        }

        if (blank(Arr::get($contacts_settings, 'phones'))) {
            $contacts_settings['phones'] = $this->makePhoneRows(Arr::get($legacy_data, 'contact_phones'));
        }

        $contacts_settings['localized'] = $this->mergeWorkingHours(
            is_array(Arr::get($contacts_settings, 'localized')) ? Arr::get($contacts_settings, 'localized') : [],
            Arr::get($legacy_data, 'work_time'),
        );

        if (blank(Arr::get($contacts_settings, 'addresses'))) {
            $contacts_settings['addresses'] = $this->makeAddressRows(Arr::get($legacy_data, 'contact_addresses'));
        }

        $map = is_array(Arr::get($contacts_settings, 'map')) ? Arr::get($contacts_settings, 'map') : [];
        $legacy_coordinates = $this->parseCoordinates(Arr::get($legacy_data, 'coordinates'));

        if (blank(Arr::get($map, 'latitude')) && $legacy_coordinates !== null) {
            $map['latitude'] = $legacy_coordinates['latitude'];
            $map['longitude'] = $legacy_coordinates['longitude'];
        }

        if (blank(Arr::get($map, 'iframe')) && filled(Arr::get($legacy_data, 'iframe_map'))) {
            $map['iframe'] = Str::trim((string) Arr::get($legacy_data, 'iframe_map'));
        }

        $contacts_settings['map'] = $map;

        return $contacts_settings;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || Str::trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function flattenStrings(mixed $value): array
    {
        if (is_string($value)) {
            return collect(explode(',', $value))
                ->map(fn (string $item): string => Str::trim($item))
                ->filter()
                ->values()
                ->all();
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->flatMap(fn (mixed $item): array => $this->flattenStrings($item))
            ->values()
            ->all();
    }

    /**
     * @param mixed $value
     * @return array<int, array{value:string, sort_order:int}>
     */
    private function makeValueRows(mixed $value): array
    {
        return collect($this->flattenStrings($value))
            ->map(fn (string $item): string => Str::lower($item))
            ->unique()
            ->values()
            ->map(fn (string $item, int $index): array => [
                'value' => $item,
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();
    }

    /**
     * @param mixed $value
     * @return array<int, array{type:string, value:string, sort_order:int}>
     */
    private function makePhoneRows(mixed $value): array
    {
        return collect($this->flattenStrings($value))
            ->unique()
            ->values()
            ->map(fn (string $item, int $index): array => [
                'type' => 'mobile',
                'value' => $item,
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();
    }

    /**
     * @param array<string, mixed> $localized
     * @param mixed $legacy_work_time
     * @return array<string, mixed>
     */
    private function mergeWorkingHours(array $localized, mixed $legacy_work_time): array
    {
        $legacy_work_time = $this->decodeJson($legacy_work_time);
        $languages = DB::table('languages')->get(['id', 'code']);

        foreach ($languages as $language) {
            $language_id = (string) $language->id;
            $content = Arr::get($legacy_work_time, $language->code);
            $content = is_array($content) ? Arr::first($this->flattenStrings($content)) : $content;

            if (! is_string($content) || Str::trim($content) === '') {
                continue;
            }

            if (! is_array(Arr::get($localized, $language_id))) {
                $localized[$language_id] = [];
            }

            if (! is_array(Arr::get($localized, "$language_id.working_hours"))) {
                $localized[$language_id]['working_hours'] = [];
            }

            if (blank(Arr::get($localized, "$language_id.working_hours.content"))) {
                $localized[$language_id]['working_hours']['content'] = Str::trim($content);
            }
        }

        return $localized;
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>
     */
    private function makeAddressRows(mixed $value): array
    {
        $legacy_addresses = $this->decodeJson($value);
        $languages = DB::table('languages')->get(['id', 'code']);
        $rows = [];

        foreach ($languages as $language) {
            $localized_value = Arr::get($legacy_addresses, $language->code);
            $localized_value = $this->flattenStrings($localized_value);

            foreach ($localized_value as $index => $address) {
                $rows[$index]['localized'][(string) $language->id] = [
                    'title' => '',
                    'description' => null,
                    'value' => $address,
                    'url' => null,
                ];
            }
        }

        return collect($rows)
            ->values()
            ->map(fn (array $row, int $index): array => [
                'sort_order' => ($index + 1) * 10,
                'localized' => $row['localized'] ?? [],
            ])
            ->all();
    }

    /**
     * @return array{latitude:float, longitude:float}|null
     */
    private function parseCoordinates(mixed $coordinates): ?array
    {
        if (! is_string($coordinates)) {
            return null;
        }

        $parts = collect(explode(',', Str::trim($coordinates)))
            ->map(fn (string $part): string => Str::trim($part))
            ->values();

        if ($parts->count() !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        return [
            'latitude' => (float) $parts[0],
            'longitude' => (float) $parts[1],
        ];
    }

    /**
     * @param array<string, mixed> $contacts_settings
     * @param array<int|string, mixed> $languages
     * @return array<string, mixed>
     */
    private function getLegacyLocalizedValues(array $contacts_settings, string $key, array $languages): array
    {
        $rows = Arr::get($contacts_settings, $key, []);
        $rows = is_array($rows) ? $rows : [];
        $values = collect($rows)
            ->map(fn (mixed $row): string => Str::trim((string) Arr::get((array) $row, 'value', '')))
            ->filter()
            ->values()
            ->all();

        return collect($languages)
            ->mapWithKeys(fn (mixed $code, mixed $language_id): array => [(string) $code => implode(', ', $values)])
            ->all();
    }

    /**
     * @param array<string, mixed> $contacts_settings
     * @param array<int|string, mixed> $languages
     * @return array<string, mixed>
     */
    private function getLegacyWorkingHours(array $contacts_settings, array $languages): array
    {
        return collect($languages)
            ->mapWithKeys(function (mixed $code, mixed $language_id) use ($contacts_settings): array {
                return [(string) $code => Arr::get($contacts_settings, "localized.$language_id.working_hours.content")];
            })
            ->all();
    }

    /**
     * @param array<string, mixed> $contacts_settings
     * @param array<int|string, mixed> $languages
     * @return array<string, mixed>
     */
    private function getLegacyAddresses(array $contacts_settings, array $languages): array
    {
        $rows = Arr::get($contacts_settings, 'addresses', []);

        return collect($languages)
            ->mapWithKeys(function (mixed $code, mixed $language_id) use ($rows): array {
                $values = collect(is_array($rows) ? $rows : [])
                    ->map(fn (mixed $row): string => Str::trim((string) Arr::get((array) $row, "localized.$language_id.value", '')))
                    ->filter()
                    ->values()
                    ->all();

                return [(string) $code => $values];
            })
            ->all();
    }

    private function getLegacyCoordinates(array $contacts_settings): ?string
    {
        $latitude = Arr::get($contacts_settings, 'map.latitude');
        $longitude = Arr::get($contacts_settings, 'map.longitude');

        return is_numeric($latitude) && is_numeric($longitude) ? "$latitude,$longitude" : null;
    }
};
