<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\CatalogFilter\Pages;

use App\Filament\Pages\Wiki\CatalogFilterWikiPage;
use App\Filament\Resources\Catalogs\CatalogFilter\CatalogFilterSetResource;
use App\Models\CatalogFilter\CatalogFilterSet;
use App\Services\CatalogFilter\CatalogFilterBootstrapService;
use App\Services\CatalogFilter\FilterGroupGeneratorService;
use App\Services\CatalogFilter\FilterValueGeneratorService;
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
                ->label(__('admin/wiki.actions.open_wiki'))
                ->url(fn (): string => CatalogFilterWikiPage::getUrl(), shouldOpenInNewTab: true),

            Action::make('refresh_index_status')
                ->label(__('admin/catalogs/catalog-filter/catalog-filter-set.actions.refresh_index_status'))
                ->action(function (): void {
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(__('admin/catalogs/catalog-filter/catalog-filter-set.notifications.index_status_refreshed'))
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

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/catalogs/catalog-filter/catalog-filter-set.notifications.all_synced', [
                                'groups_total' => (int) $group_summary['total_groups'],
                                'values_total' => (int) $value_summary['total_values'],
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
        $record->loadMissing('indexMeta');

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

        return $data;
    }

    private function refreshRecord(): void
    {
        /** @var CatalogFilterSet $record */
        $record       = $this->getRecord();
        $this->record = $record->fresh(['indexMeta']) ?? $record;

        $this->fillForm();
    }
}
