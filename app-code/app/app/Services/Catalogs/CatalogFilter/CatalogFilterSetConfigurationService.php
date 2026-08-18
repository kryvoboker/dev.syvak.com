<?php

declare(strict_types=1);

namespace App\Services\Catalogs\CatalogFilter;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroupTranslation;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CatalogFilterSetConfigurationService
{
    /**
     * @param  array<string, mixed>  $data
     * @throws Throwable
     * @return array{filter_set: CatalogFilterSet, groups_updated: int, translations_updated: int}
     */
    public function update(CatalogFilterSet $filter_set, array $data): array
    {
        try {
            return DB::transaction(function () use ($filter_set, $data): array {
                $normalized_data = $this->normalizeFilterSetData($data);
                $filter_items = (array) Arr::pull($normalized_data, 'filter_items', []);
                Arr::forget($normalized_data, 'index_meta');

                $filter_set->update($normalized_data);
                $sync_summary = $this->syncFilterItems($filter_set, $filter_items);
                app(CatalogFilterIndexFreshnessService::class)->markStale($filter_set);

                return [
                    'filter_set' => $filter_set->fresh(['indexMeta', 'groups.translations.language']) ?? $filter_set,
                    'groups_updated' => $sync_summary['groups_updated'],
                    'translations_updated' => $sync_summary['translations_updated'],
                ];
            });
        } catch (Throwable $throwable) {
            Log::channel('stack')->error(
                'Catalog filter set configuration update failed.',
                [
                    'catalog_filter_set_id' => (int) $filter_set->id,
                    'exception' => $throwable,
                ],
            );

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeFilterSetData(array $data): array
    {
        $selected_context_types = collect((array) Arr::get($data, 'context_types', []))
            ->map(fn (mixed $context_type): string => (string) $context_type)
            ->filter(fn (string $context_type): bool => filled($context_type))
            ->unique()
            ->values()
            ->all();

        if ($selected_context_types === []) {
            $selected_context_types = ['category'];
        }

        Arr::set($data, 'context_types', $selected_context_types);
        Arr::set($data, 'context_type', $selected_context_types[0]);

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filter_items
     * @return array{groups_updated: int, translations_updated: int}
     */
    private function syncFilterItems(CatalogFilterSet $filter_set, array $filter_items): array
    {
        $existing_groups_by_code = CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $filter_set->id)
            ->with('translations.language')
            ->get()
            ->keyBy('code');

        /** @var array<string, Language> $languages_by_code */
        $languages_by_code = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $languages_by_code[(string) $language->code] = $language;
        }

        $groups_updated = 0;
        $translations_updated = 0;

        foreach ($filter_items as $filter_item) {
            $group_code = (string) Arr::get($filter_item, 'code', '');

            if (blank($group_code)) {
                continue;
            }

            /** @var CatalogFilterGroup|null $group */
            $group = $existing_groups_by_code->get($group_code);

            if (! $group instanceof CatalogFilterGroup) {
                $group = new CatalogFilterGroup();
                $catalog_filter_set_id = (int) $filter_set->id;

                if ($catalog_filter_set_id > 0) {
                    $group->catalog_filter_set_id = $catalog_filter_set_id;
                }
                $group->code = $group_code;
            }

            $config_data = (array) Arr::get($filter_item, 'config', []);
            $group->fill([
                'source_type' => (string) Arr::get($filter_item, 'source_type', $group->getRawOriginal('source_type') ?? 'system'),
                'source_id' => filled(Arr::get($filter_item, 'source_id'))
                    ? (int) Arr::get($filter_item, 'source_id')
                    : null,
                'is_enabled' => (bool) Arr::get($filter_item, 'is_enabled', true),
                'sort_order' => (int) Arr::get($filter_item, 'sort_order', 0),
                'get_key' => $this->resolveGetKey($group, $filter_item, $group_code),
                'config' => $this->buildGroupConfig($group, $filter_item, $config_data, $group_code),
            ]);
            $group->save();
            $groups_updated++;

            $labels = (array) Arr::get($config_data, 'labels', []);

            foreach ($languages_by_code as $language_code => $language) {
                CatalogFilterGroupTranslation::query()->updateOrCreate(
                    [
                        'catalog_filter_group_id' => (int) $group->id,
                        'language_id' => (int) $language->id,
                    ],
                    [
                        'label' => (string) ($labels[$language_code] ?? ''),
                        'description' => null,
                    ],
                );

                $translations_updated++;
            }
        }

        return [
            'groups_updated' => $groups_updated,
            'translations_updated' => $translations_updated,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter_item
     * @param  array<string, mixed>  $config_data
     * @return array<string, mixed>
     */
    private function buildGroupConfig(
        CatalogFilterGroup $group,
        array $filter_item,
        array $config_data,
        string $group_code,
    ): array {
        $price_filter_group_name = CatalogFilterGroupSourceTypeEnum::Price->value;
        $get_data = [
            'value' => (string) Arr::get($filter_item, 'get.value', ''),
            'extra' => is_array(Arr::get($filter_item, 'get.extra', []))
                ? (array) Arr::get($filter_item, 'get.extra', [])
                : [],
        ];

        /** @var array<string, mixed> $next_config */
        $next_config = (array) ($group->config ?? []);
        $next_config['mode'] = (string) Arr::get($config_data, 'mode', $this->getDefaultFilterMode());
        $next_config['get'] = $get_data;
        $next_config['min_price'] = $group_code === $price_filter_group_name ? Arr::get($config_data, 'min_price') : null;
        $next_config['max_price'] = $group_code === $price_filter_group_name ? Arr::get($config_data, 'max_price') : null;
        $next_config['step'] = $group_code === $price_filter_group_name ? Arr::get($config_data, 'step') : null;

        return $next_config;
    }

    /**
     * @param  array<string, mixed>  $filter_item
     */
    private function resolveGetKey(CatalogFilterGroup $group, array $filter_item, string $group_code): string
    {
        $next_get_key = Str::of((string) Arr::get($filter_item, 'get.key', ''))->trim()->toString();

        if (filled($next_get_key)) {
            return $next_get_key;
        }

        if (filled((string) $group->get_key)) {
            return (string) $group->get_key;
        }

        $source_type = (string) Arr::get(
            $filter_item,
            'source_type',
            $group->getRawOriginal('source_type') ?? 'system',
        );
        $source_id = filled(Arr::get($filter_item, 'source_id'))
            ? (int) Arr::get($filter_item, 'source_id')
            : null;

        if ($source_type === CatalogFilterGroupSourceTypeEnum::Price->value) {
            return CatalogFilterGroupSourceTypeEnum::Price->value;
        }

        if ($source_type === 'attribute' && $source_id !== null && $source_id > 0) {
            return 'filters[' . $source_id . ']';
        }

        return filled($group_code) ? $group_code : 'filters';
    }

    public function getDefaultFilterMode(): string
    {
        $filter_modes = (array) config('catalog-filter.filter_modes', []);
        $default_mode = array_key_first($filter_modes);

        return is_string($default_mode) && filled($default_mode) ? $default_mode : 'multiple';
    }
}
