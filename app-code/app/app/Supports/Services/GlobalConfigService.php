<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Models\ApplicationSettings\GlobalConfig;
use Illuminate\Support\Collection;

final class GlobalConfigService
{
    public function getGlobalConfigsForForm(): Collection
    {
        return GlobalConfig::query()
            ->orderBy('key')
            ->get()
            ->map(function (GlobalConfig $global_config): array {
                $selected = false;

                return [
                    'key' => (string) $global_config->key,
                    'value' => $global_config->value,
                    'is_active' => (bool) $global_config->is_active,
                    'selected' => $selected,
                ];
            })
            ->values();
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
     * @param  array{key?: string|null, value?: string|null, is_active?: bool|null}  $data
     */
    public function saveGlobalConfig(?GlobalConfig $global_config, array $data): GlobalConfig
    {
        $record = $global_config ?? new GlobalConfig();

        $record->fill([
            'key' => $data['key'] ?? $record->key,
            'value' => array_key_exists('value', $data) ? $data['value'] : $record->value,
            'is_active' => $data['is_active'] ?? $record->is_active ?? true,
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

                $key = trim((string) ($global_config['key'] ?? ''));

                if ($key === '') {
                    return null;
                }

                $value = $global_config['value'] ?? null;

                return [
                    'key' => $key,
                    'value' => blank($value) ? null : (string) $value,
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
