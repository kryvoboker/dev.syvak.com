<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Models\ApplicationSettings\GlobalConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class GlobalConfigService
{
    public function getActiveGlobalConfigs(): Collection
    {
        return GlobalConfig::query()
            ->where('is_active', true)
            ->orderBy('key')
            ->pluck('value', 'key');
    }

    public function getGlobalConfigs(): Collection
    {
        return $this->getActiveGlobalConfigs();
    }

    public function getGlobalConfig(string $key, mixed $default = null): mixed
    {
        $cached_global_configs = app(AppSettingsService::class)->getSettings()?->global_configs;

        if ($cached_global_configs instanceof Collection && $cached_global_configs->has($key)) {
            return $cached_global_configs->get($key);
        }

        return $this->getActiveGlobalConfigs()->get($key, $default);
    }

    public function getGlobalConfigsForForm(): Collection
    {
        return GlobalConfig::query()
            ->orderBy('key')
            ->get()
            ->map(function (GlobalConfig $global_config): array {
                return [
                    'key' => (string) $global_config->key,
                    'value' => $global_config->value,
                    'is_active' => (bool) $global_config->is_active,
                    'selected' => false,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>|string  $key
     */
    public function upsertGlobalConfig(array|string $key, mixed $value = null, bool $is_active = true): GlobalConfig|Collection
    {
        if (is_array($key)) {
            return $this->upsertGlobalConfigs($key, $is_active);
        }

        return $this->upsertGlobalConfigRecord($key, $value, $is_active);
    }

    /**
     * @param  array<string, mixed>  $global_configs
     * @return Collection<string, GlobalConfig>
     */
    public function upsertGlobalConfigs(array $global_configs, bool $is_active = true): Collection
    {
        return collect($this->normalizeGlobalConfigPayloads($global_configs, $is_active))
            ->map(function (array $global_config_data): GlobalConfig {
                return $this->upsertGlobalConfigRecord(
                    $global_config_data['key'],
                    $global_config_data['value'],
                    $global_config_data['is_active'],
                );
            })
            ->keyBy(fn (GlobalConfig $global_config): string => (string) $global_config->key);
    }

    /**
     * @param  array<int, array<string, mixed>>  $global_configs
     * @return array{created_count: int, updated_count: int, deleted_count: int, total_count: int}
     */
    public function syncGlobalConfigs(array $global_configs): array
    {
        $normalized_global_configs = $this->normalizeGlobalConfigs($global_configs);
        $kept_keys = [];
        $created_count = 0;
        $updated_count = 0;

        foreach ($normalized_global_configs as $global_config_data) {
            $global_config = GlobalConfig::query()->updateOrCreate(
                ['key' => $global_config_data['key']],
                [
                    'value' => $global_config_data['value'],
                    'is_active' => $global_config_data['is_active'],
                ],
            );

            $kept_keys[] = (string) $global_config->key;

            if ($global_config->wasRecentlyCreated) {
                $created_count++;
            } else {
                $updated_count++;
            }
        }

        $deleted_count = $this->deleteMissingGlobalConfigs($kept_keys);

        $this->clearAppSettingsCache();

        return [
            'created_count' => $created_count,
            'updated_count' => $updated_count,
            'deleted_count' => $deleted_count,
            'total_count' => count($normalized_global_configs),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $global_configs
     * @return array{updated_count: int, total_count: int}
     */
    public function disableSelectedGlobalConfigs(array $global_configs): array
    {
        $normalized_global_configs = $this->normalizeGlobalConfigs($global_configs);
        $selected_keys = $this->getSelectedKeys($normalized_global_configs);

        if ($selected_keys === []) {
            return [
                'updated_count' => 0,
                'total_count' => count($normalized_global_configs),
            ];
        }

        $updated_global_configs = collect($normalized_global_configs)->map(function (array $global_config_data) use ($selected_keys): array {
            if (in_array($global_config_data['key'], $selected_keys, true)) {
                $global_config_data['is_active'] = false;
            }

            return $global_config_data;
        })->all();

        $summary = $this->syncGlobalConfigs($updated_global_configs);

        return [
            'updated_count' => count($selected_keys),
            'total_count' => $summary['total_count'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $global_configs
     * @return array{deleted_count: int, total_count: int}
     */
    public function deleteSelectedGlobalConfigs(array $global_configs): array
    {
        $normalized_global_configs = $this->normalizeGlobalConfigs($global_configs);
        $selected_keys = $this->getSelectedKeys($normalized_global_configs);

        if ($selected_keys === []) {
            return [
                'deleted_count' => 0,
                'total_count' => count($normalized_global_configs),
            ];
        }

        $remaining_global_configs = collect($normalized_global_configs)
            ->reject(fn (array $global_config_data): bool => in_array($global_config_data['key'], $selected_keys, true))
            ->values()
            ->all();

        $summary = $this->syncGlobalConfigs($remaining_global_configs);

        return [
            'deleted_count' => count($selected_keys),
            'total_count' => $summary['total_count'],
        ];
    }

    public function deleteAllGlobalConfigs(): int
    {
        $deleted_count = GlobalConfig::query()->delete();

        $this->clearAppSettingsCache();

        return $deleted_count;
    }

    /**
     * @param  array<string, mixed>|string  $key
     */
    public function deleteGlobalConfig(array|string $key): int
    {
        $keys = $this->normalizeGlobalConfigKeys($key);

        if ($keys === []) {
            return 0;
        }

        $deleted_count = GlobalConfig::query()
            ->whereIn('key', $keys)
            ->delete();

        if ($deleted_count > 0) {
            $this->clearAppSettingsCache();
        }

        return $deleted_count;
    }

    /**
     * @param  array<string, mixed>|string  $key
     */
    public function disableGlobalConfig(array|string $key): int
    {
        $keys = $this->normalizeGlobalConfigKeys($key);

        if ($keys === []) {
            return 0;
        }

        $affected_rows = GlobalConfig::query()
            ->whereIn('key', $keys)
            ->update([
                'is_active' => false,
            ]);

        if ($affected_rows > 0) {
            $this->clearAppSettingsCache();
        }

        return $affected_rows;
    }

    /**
     * @param  array{key?: string|null, value?: string|null, is_active?: bool|null}  $data
     */
    public function saveGlobalConfig(?GlobalConfig $global_config, array $data): GlobalConfig
    {
        $record = $global_config ?? new GlobalConfig();

        $record->fill([
            'key' => $data['key'] ?? $record->key,
            'value' => array_key_exists('value', $data)
                ? $this->normalizeGlobalConfigValue($data['value'])
                : $record->value,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) ($record->is_active ?? true),
        ]);
        $record->save();

        $this->clearAppSettingsCache();

        return $record;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function disableGlobalConfigsByIds(array $ids): int
    {
        $affected_rows = GlobalConfig::query()
            ->whereKey($ids)
            ->update([
                'is_active' => false,
            ]);

        if ($affected_rows > 0) {
            $this->clearAppSettingsCache();
        }

        return $affected_rows;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function deleteGlobalConfigsByIds(array $ids): int
    {
        $deleted_count = GlobalConfig::query()
            ->whereKey($ids)
            ->delete();

        if ($deleted_count > 0) {
            $this->clearAppSettingsCache();
        }

        return $deleted_count;
    }

    /**
     * @param  array<string, mixed>  $global_configs
     * @return array<int, array{key: string, value: ?string, is_active: bool}>
     */
    private function normalizeGlobalConfigPayloads(array $global_configs, bool $default_is_active): array
    {
        $normalized_global_configs = [];

        foreach ($global_configs as $key => $value) {
            $config_key = Str::trim($key);

            if ($config_key === '') {
                continue;
            }

            if (is_array($value) && Arr::hasAny($value, ['value', 'is_active'])) {
                $normalized_global_configs[] = [
                    'key' => $config_key,
                    'value' => array_key_exists('value', $value) ? $this->normalizeGlobalConfigValue($value['value']) : null,
                    'is_active' => array_key_exists('is_active', $value) ? (bool) $value['is_active'] : $default_is_active,
                ];

                continue;
            }

            $normalized_global_configs[] = [
                'key' => $config_key,
                'value' => $this->normalizeGlobalConfigValue($value),
                'is_active' => $default_is_active,
            ];
        }

        return $normalized_global_configs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $global_configs
     * @return array<int, array{key: string, value: ?string, is_active: bool, selected: bool}>
     */
    private function normalizeGlobalConfigs(array $global_configs): array
    {
        return collect($global_configs)
            ->map(function (mixed $global_config): ?array {
                if (! is_array($global_config)) {
                    return null;
                }

                $key = Str::trim((string) ($global_config['key'] ?? ''));

                if ($key === '') {
                    return null;
                }

                $value = $global_config['value'] ?? null;

                return [
                    'key' => $key,
                    'value' => $this->normalizeGlobalConfigValue($value),
                    'is_active' => (bool) ($global_config['is_active'] ?? true),
                    'selected' => (bool) ($global_config['selected'] ?? false),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{key: string, value: ?string, is_active: bool, selected: bool}>  $global_configs
     * @return array<int, string>
     */
    private function getSelectedKeys(array $global_configs): array
    {
        return collect($global_configs)
            ->filter(fn (array $global_config): bool => $global_config['selected'])
            ->pluck('key')
            ->all();
    }

    /**
     * @param  array<string, mixed>|string  $key
     * @return array<int, string>
     */
    private function normalizeGlobalConfigKeys(array|string $key): array
    {
        if (is_string($key)) {
            $trimmed_key = Str::trim($key);

            return $trimmed_key === '' ? [] : [$trimmed_key];
        }

        $keys = Arr::isAssoc($key) ? array_keys($key) : $key;

        return collect($keys)
            ->filter(fn (mixed $config_key): bool => Str::trim((string) $config_key) !== '')
            ->map(fn (mixed $config_key): string => Str::trim((string) $config_key))
            ->values()
            ->all();
    }

    private function upsertGlobalConfigRecord(string $key, mixed $value, bool $is_active): GlobalConfig
    {
        $global_config = GlobalConfig::query()->updateOrCreate(
            ['key' => Str::trim($key)],
            [
                'value' => $this->normalizeGlobalConfigValue($value),
                'is_active' => $is_active,
            ],
        );

        $this->clearAppSettingsCache();

        return $global_config;
    }

    private function normalizeGlobalConfigValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
        }

        $string_value = (string) $value;

        return $string_value === '' ? null : $string_value;
    }

    /**
     * @param  array<int, string>  $kept_keys
     */
    private function deleteMissingGlobalConfigs(array $kept_keys): int
    {
        $query = GlobalConfig::query();

        if ($kept_keys !== []) {
            $query->whereNotIn('key', $kept_keys);
        }

        return $query->delete();
    }

    private function clearAppSettingsCache(): void
    {
        app(AppSettingsService::class)->removeSettings();
    }
}
