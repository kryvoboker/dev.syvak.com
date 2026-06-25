<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter\Pages;

use App\Enums\CatalogFilter\CatalogFilterGroupSourceTypeEnum;
use App\Filament\Pages\Wiki\CatalogFilterWikiPage;
use App\Filament\Resources\Catalogs\CatalogFilter\CatalogFilterSetResource;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroupTranslation;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\CatalogFilterBootstrapService;
use App\Services\Catalogs\CatalogFilter\CatalogFilterIndexRebuildService;
use App\Services\Catalogs\CatalogFilter\FilterGroupGeneratorService;
use App\Services\Catalogs\CatalogFilter\FilterValueGeneratorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Throwable;

class EditCatalogFilterSet extends EditRecord
{
    protected static string $resource = CatalogFilterSetResource::class;

    public function getTitle(): string
    {
        return __('admin/catalogs/catalog-filter/catalog-filter-set.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/catalogs/catalog-filter/catalog-filter-set.navigation_label');
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $filter_set = app(CatalogFilterBootstrapService::class)->bootstrapDefaultCategorySet();

        parent::mount($filter_set->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/wiki/wiki.actions.open_wiki'))
                ->url(fn (): string => CatalogFilterWikiPage::getUrl(), shouldOpenInNewTab: true),

            Action::make('refresh_index_status')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.refresh_index_status'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record  = $this->getRecord();
                    $summary = app(CatalogFilterIndexRebuildService::class)->rebuild($record);

                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_status_refreshed', [
                                'rows_total'    => (int) ($summary['rows_total'] ?? 0),
                                'index_version' => (int) ($summary['index_version'] ?? 0),
                                'status'        => (string) ($summary['status'] ?? 'ok'),
                            ]),
                        )
                        ->success()
                        ->send();
                }),

            Action::make('sync_groups')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.sync_groups'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record  = $this->getRecord();
                    $summary = app(FilterGroupGeneratorService::class)->sync($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.groups_synced', [
                                'created' => (int) $summary['created_count'],
                                'updated' => (int) $summary['updated_count'],
                                'total'   => (int) $summary['total_groups'],
                            ]),
                        )
                        ->success()
                        ->send();

                    $this->refreshRecord();
                }),

            Action::make('sync_values')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.sync_values'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record  = $this->getRecord();
                    $summary = app(FilterValueGeneratorService::class)->sync($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.values_synced', [
                                'created' => (int) $summary['created_count'],
                                'updated' => (int) $summary['updated_count'],
                                'removed' => (int) $summary['removed_count'],
                                'total'   => (int) $summary['total_values'],
                            ]),
                        )
                        ->success()
                        ->send();

                    $this->refreshRecord();
                }),

            Action::make('sync_all')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.sync_all'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record = $this->getRecord();

                    $group_summary = app(FilterGroupGeneratorService::class)->sync($record);
                    $value_summary = app(FilterValueGeneratorService::class)->sync($record);
                    $index_summary = app(CatalogFilterIndexRebuildService::class)->rebuild($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.all_synced', [
                                'groups_total' => (int) $group_summary['total_groups'],
                                'values_total' => (int) $value_summary['total_values'],
                            ]) . ' ' . __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_status_refreshed', [
                                'rows_total'    => (int) ($index_summary['rows_total'] ?? 0),
                                'index_version' => (int) ($index_summary['index_version'] ?? 0),
                                'status'        => (string) ($index_summary['status'] ?? 'ok'),
                            ]),
                        )
                        ->success()
                        ->send();

                    $this->refreshRecord();
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var CatalogFilterSet $record */
        $record = $this->getRecord();
        $record->loadMissing('indexMeta', 'groups.translations.language');

        $selected_context_types = is_array($record->context_types) && $record->context_types !== []
            ? $record->context_types
            : [(string) $record->getRawOriginal('context_type')];

        $index_meta = $record->indexMeta;

        Arr::set($data, 'context_types', $selected_context_types);
        Arr::set($data, 'index_meta.active_index_version', $index_meta?->active_index_version);
        Arr::set($data, 'index_meta.building_index_version', $index_meta?->building_index_version);
        Arr::set($data, 'index_meta.last_status', $index_meta?->getRawOriginal('last_status'));
        Arr::set($data, 'index_meta.last_run_mode', $index_meta?->getRawOriginal('last_run_mode'));
        Arr::set($data, 'index_meta.last_progress_percent', $index_meta?->last_progress_percent);
        Arr::set($data, 'index_meta.index_rows_total', $index_meta?->index_rows_total);
        Arr::set($data, 'index_meta.last_full_rebuild_at', $index_meta?->getRawOriginal('last_full_rebuild_at'));
        Arr::set($data, 'index_meta.last_incremental_sync_at', $index_meta?->getRawOriginal('last_incremental_sync_at'));
        Arr::set($data, 'index_meta.rebuild_lock_key', $index_meta?->rebuild_lock_key);
        Arr::set($data, 'index_meta.rebuild_lock_acquired_at', $index_meta?->getRawOriginal('rebuild_lock_acquired_at'));

        $filter_items = $record->groups
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->reject(fn (CatalogFilterGroup $group): bool => (string) $group->code === 'stock')
            ->map(function (CatalogFilterGroup $group): array {
                $config_data = (array) ($group->config ?? []);
                $get_data    = (array) ($config_data['get'] ?? []);

                $labels = $group->translations
                    ->mapWithKeys(function (CatalogFilterGroupTranslation $translation): array {
                        $language_code = (string) optional($translation->language)->code;

                        if (blank($language_code)) {
                            return [];
                        }

                        return [
                            $language_code => (string) ($translation->label ?? ''),
                        ];
                    })
                    ->all();

                return [
                    'code'        => (string) $group->code,
                    'source_type' => (string) $group->getRawOriginal('source_type'),
                    'source_id'   => $group->source_id,
                    'is_enabled'  => (bool) $group->is_enabled,
                    'sort_order'  => (int) $group->sort_order,
                    'get'         => [
                        'key'   => (string) ($group->get_key ?? ''),
                        'value' => (string) ($get_data['value'] ?? ''),
                        'extra' => is_array($get_data['extra'] ?? null) ? (array) $get_data['extra'] : [],
                    ],
                    'config' => [
                        'mode'      => (string) ($config_data['mode'] ?? $this->getDefaultFilterMode()),
                        'min_price' => $config_data['min_price'] ?? null,
                        'max_price' => $config_data['max_price'] ?? null,
                        'step'      => $config_data['step'] ?? null,
                        'labels'    => $labels,
                    ],
                ];
            })
            ->values()
            ->all();

        Arr::set($data, 'filter_items', $filter_items);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
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

        $filter_items = (array) Arr::pull($data, 'filter_items', []);
        $this->syncFilterItems($filter_items);

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filter_items
     */
    private function syncFilterItems(array $filter_items): void
    {
        /** @var CatalogFilterSet $record */
        $record = $this->getRecord();

        $existing_groups_by_code = CatalogFilterGroup::query()
            ->where('catalog_filter_set_id', (int) $record->id)
            ->with('translations.language')
            ->get()
            ->keyBy('code');

        /** @var array<string, Language> $languages_by_code */
        $languages_by_code = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $languages_by_code[(string) $language->code] = $language;
        }

        $price_filter_group_name = CatalogFilterGroupSourceTypeEnum::Price->value;

        foreach ($filter_items as $filter_item) {
            $group_code = (string) Arr::get($filter_item, 'code', '');

            if (blank($group_code)) {
                continue;
            }

            /** @var CatalogFilterGroup|null $group */
            $group = $existing_groups_by_code->get($group_code);

            $is_new_group = ! $group instanceof CatalogFilterGroup;

            if ($is_new_group) {
                $group                        = new CatalogFilterGroup();
                $group->catalog_filter_set_id = (int) $record->id;
                $group->code                  = $group_code;
            }

            $config_data = (array) Arr::get($filter_item, 'config', []);
            $get_data    = [
                'value' => (string) Arr::get($filter_item, 'get.value', ''),
                'extra' => is_array(Arr::get($filter_item, 'get.extra', []))
                    ? (array) Arr::get($filter_item, 'get.extra', [])
                    : [],
            ];

            $next_config = array_merge(
                (array) ($group->config ?? []),
                [
                    'mode'      => (string) Arr::get($config_data, 'mode', $this->getDefaultFilterMode()),
                    'get'       => $get_data,
                    'min_price' => $group_code === $price_filter_group_name ? Arr::get($config_data, 'min_price') : null,
                    'max_price' => $group_code === $price_filter_group_name ? Arr::get($config_data, 'max_price') : null,
                    'step'      => $group_code === $price_filter_group_name ? Arr::get($config_data, 'step') : null,
                ],
            );

            $next_get_key = trim((string) Arr::get($filter_item, 'get.key', ''));

            if (blank($next_get_key)) {
                $next_get_key = filled((string) $group->get_key)
                    ? (string) $group->get_key
                    : $this->resolveDefaultGetKey(
                        source_type: (string) Arr::get($filter_item, 'source_type', $group->getRawOriginal('source_type') ?? 'system'),
                        source_id: filled(Arr::get($filter_item, 'source_id'))
                            ? (int) Arr::get($filter_item, 'source_id')
                            : null,
                        group_code: $group_code,
                    );
            }

            $group->fill([
                'source_type' => (string) Arr::get($filter_item, 'source_type', $group->getRawOriginal('source_type') ?? 'system'),
                'source_id'   => filled(Arr::get($filter_item, 'source_id'))
                    ? (int) Arr::get($filter_item, 'source_id')
                    : null,
                'is_enabled' => (bool) Arr::get($filter_item, 'is_enabled', true),
                'sort_order' => (int) Arr::get($filter_item, 'sort_order', 0),
                'get_key'    => $next_get_key,
                'config'     => $next_config,
            ]);
            $group->save();

            $labels = (array) Arr::get($config_data, 'labels', []);

            foreach ($languages_by_code as $language_code => $language) {
                CatalogFilterGroupTranslation::query()->updateOrCreate(
                    [
                        'catalog_filter_group_id' => (int) $group->id,
                        'language_id'             => (int) $language->id,
                    ],
                    [
                        'label'       => (string) ($labels[$language_code] ?? ''),
                        'description' => null,
                    ],
                );
            }
        }
    }

    private function getDefaultFilterMode(): string
    {
        $filter_modes = (array) config('catalog-filter.filter_modes', []);
        $default_mode = array_key_first($filter_modes);

        return is_string($default_mode) && filled($default_mode) ? $default_mode : 'multiple';
    }

    private function resolveDefaultGetKey(string $source_type, ?int $source_id, string $group_code): string
    {
        $price_filter_group_name = CatalogFilterGroupSourceTypeEnum::Price->value;

        if ($source_type === $price_filter_group_name) {
            return $price_filter_group_name;
        }

        if ($source_type === 'attribute' && $source_id !== null && $source_id > 0) {
            return 'filters[' . $source_id . ']';
        }

        if (filled($group_code)) {
            return $group_code;
        }

        return 'filters';
    }

    private function refreshRecord(): void
    {
        /** @var CatalogFilterSet $record */
        $record       = $this->getRecord();
        $this->record = $record->fresh(['indexMeta', 'groups.translations.language']) ?? $record;

        $this->fillForm();
    }
}
