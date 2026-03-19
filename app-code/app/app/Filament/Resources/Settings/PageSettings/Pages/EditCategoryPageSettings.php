<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\PageSettings\Pages;

use App\Filament\Resources\Settings\PageSettings\CategoryPageSettingResource;
use App\Models\Settings\Language;
use App\Models\Settings\PageSettings\PageSetting;
use App\Models\Settings\PageSettings\PageSettingItem;
use App\Models\Settings\PageSettings\PageSettingTranslation;
use App\Services\PageSettings\CategoryPageFilterSyncService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditCategoryPageSettings extends EditRecord
{
    protected static string $resource = CategoryPageSettingResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/category_page_settings.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/category_page_settings.navigation_label');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'admin/page-settings'                        => __('admin/default.menu.item_page_settings'),
            CategoryPageSettingResource::getUrl('index') => __('admin/settings/category_page_settings.navigation_label'),
        ];
    }

    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        app(CategoryPageFilterSyncService::class)->sync($page_setting);
        $page_setting = $page_setting->fresh(['translations.language', 'items']) ?? $page_setting;

        parent::mount($page_setting->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_filters')
                ->label(__('admin/settings/category_page_settings.actions.sync_filters'))
                ->action(function (): void {
                    $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
                    $summary      = app(CategoryPageFilterSyncService::class)->sync($page_setting);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(
                            __('admin/settings/category_page_settings.notifications.filters_synced', [
                                'created' => (int) $summary['created_count'],
                                'updated' => (int) $summary['updated_count'],
                                'removed' => (int) $summary['removed_count'],
                            ]),
                        )
                        ->success()
                        ->send();

                    $this->record = $page_setting->fresh(['translations.language', 'items']) ?? $page_setting;
                    $this->fillForm();
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PageSetting $record */
        $record = $this->getRecord();
        $record->loadMissing(['translations.language', 'items']);

        $data['localized_content'] = $this->mapLocalizedContentForForm($record);
        $data['sorting_items']     = $this->mapItemsForForm($record, PageSetting::ITEM_TYPE_SORTING);
        $data['filter_items']      = $this->mapItemsForForm($record, PageSetting::ITEM_TYPE_FILTER);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PageSetting $record */
        $record->update([
            'is_sorting_enabled'   => (bool) Arr::get($data, 'is_sorting_enabled', true),
            'is_filtering_enabled' => (bool) Arr::get($data, 'is_filtering_enabled', true),
            'settings'             => $this->normalizeSettingsContract($record, $data),
        ]);

        $this->syncTranslationsFromForm($record, (array) Arr::get($data, 'localized_content', []));
        $this->syncItemsFromForm(
            $record,
            PageSetting::ITEM_TYPE_SORTING,
            (array) Arr::get($data, 'sorting_items', []),
        );
        $this->syncItemsFromForm(
            $record,
            PageSetting::ITEM_TYPE_FILTER,
            (array) Arr::get($data, 'filter_items', []),
        );

        return $record->fresh(['translations.language', 'items']) ?? $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSettingsContract(PageSetting $record, array $data): array
    {
        $settings = $record->settings;

        if (! is_array($settings)) {
            $settings = [];
        }

        $settings = array_replace_recursive(
            [
                'meta' => ['contract_version' => 1],
                'ui'   => [
                    'sorting' => ['enabled' => true],
                    'filters' => ['enabled' => true],
                ],
            ],
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 1);
        Arr::set($settings, 'ui.sorting.enabled', (bool) Arr::get($data, 'is_sorting_enabled', true));
        Arr::set($settings, 'ui.filters.enabled', (bool) Arr::get($data, 'is_filtering_enabled', true));

        return $settings;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function mapLocalizedContentForForm(PageSetting $record): array
    {
        $translations_by_language = $record->translations
            ->keyBy(fn (PageSettingTranslation $translation): string => (string) $translation->language?->code);
        $localized_content = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_code = (string) $language->code;
            $content       = $translations_by_language->get($language_code)?->content;

            if (! is_array($content)) {
                $content = [];
            }

            $localized_content[$language_code] = [
                'sorting' => [
                    'title'       => (string) Arr::get($content, 'sorting.title', ''),
                    'description' => (string) Arr::get($content, 'sorting.description', ''),
                ],
                'filters' => [
                    'title'             => (string) Arr::get($content, 'filters.title', ''),
                    'description'       => (string) Arr::get($content, 'filters.description', ''),
                    'drawer_title'      => (string) Arr::get($content, 'filters.drawer_title', ''),
                    'apply_button_text' => (string) Arr::get($content, 'filters.apply_button_text', ''),
                    'clear_button_text' => (string) Arr::get($content, 'filters.clear_button_text', ''),
                ],
                'option_labels' => $this->normalizeStringMap((array) Arr::get($content, 'option_labels', [])),
            ];
        }

        return $localized_content;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapItemsForForm(PageSetting $record, string $type): array
    {
        return $record->items
            ->where('type', $type)
            ->sortBy('sort_order')
            ->values()
            ->map(function (PageSettingItem $item): array {
                $get_payload    = is_array($item->get) ? $item->get : [];
                $config_payload = is_array($item->config) ? $item->config : [];

                return [
                    'code'        => (string) $item->code,
                    'source_type' => (string) ($item->source_type ?? ''),
                    'source_id'   => $item->source_id,
                    'is_enabled'  => (bool) $item->is_enabled,
                    'sort_order'  => (int) $item->sort_order,
                    'get'         => [
                        'key'   => (string) Arr::get($get_payload, 'key', ''),
                        'value' => Arr::get($get_payload, 'value'),
                        'extra' => $this->normalizeStringMap((array) Arr::get($get_payload, 'extra', [])),
                    ],
                    'config' => $this->normalizeStringMap($config_payload),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $localized_content
     */
    private function syncTranslationsFromForm(PageSetting $record, array $localized_content): void
    {
        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_code    = (string) $language->code;
            $language_content = Arr::get($localized_content, $language_code, []);

            PageSettingTranslation::query()->updateOrCreate(
                [
                    'page_setting_id' => (int) $record->id,
                    'language_id'     => (int) $language->id,
                ],
                [
                    'content' => [
                        'sorting' => [
                            'title'       => (string) Arr::get($language_content, 'sorting.title', ''),
                            'description' => (string) Arr::get($language_content, 'sorting.description', ''),
                        ],
                        'filters' => [
                            'title'             => (string) Arr::get($language_content, 'filters.title', ''),
                            'description'       => (string) Arr::get($language_content, 'filters.description', ''),
                            'drawer_title'      => (string) Arr::get($language_content, 'filters.drawer_title', ''),
                            'apply_button_text' => (string) Arr::get($language_content, 'filters.apply_button_text', ''),
                            'clear_button_text' => (string) Arr::get($language_content, 'filters.clear_button_text', ''),
                        ],
                        'option_labels' => $this->normalizeStringMap(
                            (array) Arr::get($language_content, 'option_labels', []),
                        ),
                    ],
                ],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncItemsFromForm(PageSetting $record, string $type, array $rows): void
    {
        $rows_collection = collect($rows)
            ->filter(fn (array $row): bool => filled((string) Arr::get($row, 'code')))
            ->values();

        $codes = $rows_collection
            ->pluck('code')
            ->map(fn (mixed $code): string => (string) $code)
            ->all();

        PageSettingItem::query()
            ->where('page_setting_id', (int) $record->id)
            ->where('type', $type)
            ->whereNotIn('code', $codes)
            ->delete();

        foreach ($rows_collection as $row) {
            $item = PageSettingItem::query()->firstOrNew([
                'page_setting_id' => (int) $record->id,
                'type'            => $type,
                'code'            => (string) Arr::get($row, 'code'),
            ]);

            $item->fill([
                'source_type' => $this->resolveItemSourceType($item, $row),
                'source_id'   => $this->resolveItemSourceId($item, $row),
                'is_enabled'  => (bool) Arr::get($row, 'is_enabled', true),
                'sort_order'  => (int) Arr::get($row, 'sort_order', 0),
                'get'         => [
                    'key'   => (string) Arr::get($row, 'get.key', ''),
                    'value' => Arr::get($row, 'get.value'),
                    'extra' => $this->normalizeStringMap((array) Arr::get($row, 'get.extra', [])),
                ],
                'config' => $this->normalizeStringMap((array) Arr::get($row, 'config', [])),
            ]);

            $item->save();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveItemSourceType(PageSettingItem $item, array $row): ?string
    {
        $source_type = Arr::get($row, 'source_type');

        if (filled($source_type)) {
            return (string) $source_type;
        }

        return $item->source_type;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveItemSourceId(PageSettingItem $item, array $row): ?int
    {
        $source_id = Arr::get($row, 'source_id');

        if (is_numeric($source_id)) {
            return (int) $source_id;
        }

        return $item->source_id;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function normalizeStringMap(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (mixed $value, mixed $key): array {
                return [
                    (string) $key => (string) $value,
                ];
            })
            ->all();
    }
}
