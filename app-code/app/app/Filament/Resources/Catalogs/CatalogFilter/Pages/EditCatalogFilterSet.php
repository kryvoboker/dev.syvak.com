<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter\Pages;

use App\Filament\Pages\Wiki\CatalogFilterWikiPage;
use App\Filament\Resources\Catalogs\CatalogFilter\CatalogFilterSetResource;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroup;
use App\Models\Catalogs\CatalogFilter\CatalogFilterGroupTranslation;
use App\Models\Catalogs\CatalogFilter\CatalogFilterSet;
use App\Services\Catalogs\CatalogFilter\CatalogFilterBootstrapService;
use App\Services\Catalogs\CatalogFilter\CatalogFilterIndexRebuildDispatcherService;
use App\Services\Catalogs\CatalogFilter\CatalogFilterSetConfigurationService;
use App\Services\Catalogs\CatalogFilter\FilterGroupGeneratorService;
use App\Services\Catalogs\CatalogFilter\FilterValueGeneratorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LogicException;
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

            Action::make('rebuild_index')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.rebuild_index'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record = $this->getRecord();
                    $summary = app(CatalogFilterIndexRebuildDispatcherService::class)->dispatch($record);

                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __(
                                $summary['status'] === 'queued'
                                    ? 'admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_rebuild_queued'
                                    : 'admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_rebuilt',
                                [
                                'rows_total' => (int) ($summary['rows_total'] ?? 0),
                                'index_version' => (int) ($summary['index_version'] ?? 0),
                                'status' => (string) ($summary['status'] ?? 'ok'),
                                ],
                            ),
                        )
                        ->success()
                        ->send();
                }),

            Action::make('refresh_index_status')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.refresh_index_status'))
                ->action(function (): void {
                    $this->refreshRecord();

                    /** @var CatalogFilterSet $record */
                    $record = $this->getRecord();
                    $status = $record->indexMeta?->getRawOriginal('last_status') ?? 'unknown';

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_status_refreshed', [
                                'status' => (string) $status,
                            ]),
                        )
                        ->success()
                        ->send();
                }),

            Action::make('sync_groups')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.sync_groups'))
                ->action(function (): void {
                    /** @var CatalogFilterSet $record */
                    $record = $this->getRecord();
                    $summary = app(FilterGroupGeneratorService::class)->sync($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.groups_synced', [
                                'created' => (int) $summary['created_count'],
                                'updated' => (int) $summary['updated_count'],
                                'disabled' => (int) ($summary['disabled_count'] ?? 0),
                                'total' => (int) $summary['total_groups'],
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
                    $record = $this->getRecord();
                    $summary = app(FilterValueGeneratorService::class)->sync($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.values_synced', [
                                'created' => (int) $summary['created_count'],
                                'updated' => (int) $summary['updated_count'],
                                'removed' => (int) $summary['removed_count'],
                                'total' => (int) $summary['total_values'],
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
                    $index_summary = app(CatalogFilterIndexRebuildDispatcherService::class)->dispatch($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.all_synced', [
                                'groups_total' => (int) $group_summary['total_groups'],
                                'values_total' => (int) $value_summary['total_values'],
                            ]) . ' ' . __(
                                $index_summary['status'] === 'queued'
                                    ? 'admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_rebuild_queued'
                                    : 'admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_rebuilt',
                                [
                                    'rows_total' => (int) ($index_summary['rows_total'] ?? 0),
                                    'index_version' => (int) ($index_summary['index_version'] ?? 0),
                                    'status' => (string) ($index_summary['status'] ?? 'ok'),
                                ],
                            ),
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
                $get_data = (array) ($config_data['get'] ?? []);

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
                    'code' => (string) $group->code,
                    'source_type' => (string) $group->getRawOriginal('source_type'),
                    'source_id' => $group->source_id,
                    'is_enabled' => (bool) $group->is_enabled,
                    'sort_order' => (int) $group->sort_order,
                    'get' => [
                        'key' => (string) ($group->get_key ?? ''),
                        'value' => (string) ($get_data['value'] ?? ''),
                        'extra' => is_array($get_data['extra'] ?? null) ? (array) $get_data['extra'] : [],
                    ],
                    'config' => [
                        'mode' => (string) ($config_data['mode'] ?? app(CatalogFilterSetConfigurationService::class)->getDefaultFilterMode()),
                        'min_price' => $config_data['min_price'] ?? null,
                        'max_price' => $config_data['max_price'] ?? null,
                        'step' => $config_data['step'] ?? null,
                        'labels' => $labels,
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
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof CatalogFilterSet) {
            throw new LogicException('Catalog filter set record has invalid type.');
        }

        $result = app(CatalogFilterSetConfigurationService::class)->update($record, $data);

        return $result['filter_set'];
    }

    private function refreshRecord(): void
    {
        /** @var CatalogFilterSet $record */
        $record = $this->getRecord();
        $this->record = $record->fresh(['indexMeta', 'groups.translations.language']) ?? $record;

        $this->fillForm();
    }
}
