<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Pages;

use App\Filament\Pages\Wiki\CategoryPageSettingsWikiPage;
use App\Filament\Resources\PageSettings\Category\CategoryPageSettingResource;
use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Models\PageSettings\PageSettingItem;
use App\Models\PageSettings\PageSettingTranslation;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();
        $page_setting = $page_setting->fresh(['translations.language', 'items']) ?? $page_setting;

        parent::mount($page_setting->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_wiki')
                ->label(__('admin/settings/category_page_settings.actions.open_wiki'))
                ->url(fn (): string => CategoryPageSettingsWikiPage::getUrl(), shouldOpenInNewTab: true),
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
        $record_settings           = is_array($record->settings) ? $record->settings : [];

        $data['products_per_page_limit'] = (int) Arr::get(
            $record_settings,
            'pagination.products_per_page_limit',
            (int) config('app.page_settings.category.products_per_page_limit', 20),
        );
        $data['is_ajax_products_loading_enabled'] = (bool) Arr::get(
            $record_settings,
            'pagination.ajax_products_loading_enabled',
            (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
        );
        $data['product_image_width'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'images.products.width',
                (int) config('app.page_settings.category.product_image_width', 420),
            ),
        );
        $data['product_image_height'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'images.products.height',
                (int) config('app.page_settings.category.product_image_height', 420),
            ),
        );
        $data['category_upload_max_size_mb'] = max(
            1,
            (int) ceil(
                (
                    (int) Arr::get(
                        $record_settings,
                        'admin.upload.max_size_kb',
                        (int) config('app.images.category.upload.max_size_kb', 5120),
                    )
                ) / 1024,
            ),
        );
        $data['category_image_upload_directory'] = (string) Arr::get(
            $record_settings,
            'admin.upload.directory',
            normalize_upload_path_template((string) config('app.images.category.image_path', 'images/categories/' . date('Y/m'))),
        );
        $data['category_no_image_path'] = (string) Arr::get(
            $record_settings,
            'admin.images.no_image.path',
            (string) config('app.images.category.no_image', 'images/no-image.png'),
        );
        $data['category_preview_list_width'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'admin.images.preview_in_list.width',
                (int) config('app.images.category.preview_in_list_in_admin.width', 100),
            ),
        );
        $data['category_preview_list_height'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'admin.images.preview_in_list.height',
                (int) config('app.images.category.preview_in_list_in_admin.height', 100),
            ),
        );
        $data['category_preview_page_width'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'admin.images.preview_in_page.width',
                (int) config('app.images.category.preview_in_page_in_admin.width', 500),
            ),
        );
        $data['category_preview_page_height'] = max(
            1,
            (int) Arr::get(
                $record_settings,
                'admin.images.preview_in_page.height',
                (int) config('app.images.category.preview_in_page_in_admin.height', 500),
            ),
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PageSetting $record */
        $record->update([
            'is_sorting_enabled' => (bool) Arr::get($data, 'is_sorting_enabled', true),
            'settings'           => $this->normalizeSettingsContract($record, $data),
        ]);

        $this->syncTranslationsFromForm($record, (array) Arr::get($data, 'localized_content', []));
        $this->syncItemsFromForm(
            $record,
            PageSetting::ITEM_TYPE_SORTING,
            (array) Arr::get($data, 'sorting_items', []),
        );

        PageSettingItem::query()
            ->where('page_setting_id', (int) $record->id)
            ->where('type', PageSetting::ITEM_TYPE_FILTER)
            ->delete();

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
                ],
                'pagination' => [
                    'products_per_page_limit'       => (int) config('app.page_settings.category.products_per_page_limit', 20),
                    'ajax_products_loading_enabled' => (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
                ],
                'images' => [
                    'products' => [
                        'width'  => (int) config('app.page_settings.category.product_image_width', 420),
                        'height' => (int) config('app.page_settings.category.product_image_height', 420),
                    ],
                ],
                'admin' => [
                    'upload' => [
                        'max_size_kb' => (int) config('app.images.category.upload.max_size_kb', 5120),
                        'directory'   => normalize_upload_path_template((string) config('app.images.category.image_path', 'images/categories/' . date('Y/m'))),
                    ],
                    'images' => [
                        'no_image' => [
                            'path' => (string) config('app.images.category.no_image', 'images/no-image.png'),
                        ],
                        'preview_in_list' => [
                            'width'  => (int) config('app.images.category.preview_in_list_in_admin.width', 100),
                            'height' => (int) config('app.images.category.preview_in_list_in_admin.height', 100),
                        ],
                        'preview_in_page' => [
                            'width'  => (int) config('app.images.category.preview_in_page_in_admin.width', 500),
                            'height' => (int) config('app.images.category.preview_in_page_in_admin.height', 500),
                        ],
                    ],
                ],
            ],
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 1);
        Arr::set($settings, 'ui.sorting.enabled', (bool) Arr::get($data, 'is_sorting_enabled', true));
        Arr::set($settings, 'pagination.products_per_page_limit', max(1, (int) Arr::get($data, 'products_per_page_limit', 20)));
        Arr::set($settings, 'pagination.ajax_products_loading_enabled', (bool) Arr::get($data, 'is_ajax_products_loading_enabled', true));
        Arr::set($settings, 'images.products.width', max(1, (int) Arr::get($data, 'product_image_width', 420)));
        Arr::set($settings, 'images.products.height', max(1, (int) Arr::get($data, 'product_image_height', 420)));
        Arr::set($settings, 'admin.upload.max_size_kb', max(1, (int) Arr::get($data, 'category_upload_max_size_mb', 5) * 1024));
        Arr::set(
            $settings,
            'admin.upload.directory',
            normalize_upload_path_template((string) Arr::get($data, 'category_image_upload_directory', 'images/categories/{year}/{month}')),
        );
        Arr::set($settings, 'admin.images.no_image.path', (string) Arr::get($data, 'category_no_image_path', 'images/no-image.png'));
        Arr::set($settings, 'admin.images.preview_in_list.width', max(1, (int) Arr::get($data, 'category_preview_list_width', 100)));
        Arr::set($settings, 'admin.images.preview_in_list.height', max(1, (int) Arr::get($data, 'category_preview_list_height', 100)));
        Arr::set($settings, 'admin.images.preview_in_page.width', max(1, (int) Arr::get($data, 'category_preview_page_width', 500)));
        Arr::set($settings, 'admin.images.preview_in_page.height', max(1, (int) Arr::get($data, 'category_preview_page_height', 500)));
        Arr::forget($settings, ['ui.filters']);

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

        foreach (new Language()->getActiveLanguages() as $language) {
            $language_code = (string) $language->code;
            /** @var PageSettingTranslation|null $translation */
            $translation = $translations_by_language->get($language_code);
            $content     = $translation?->content;

            if (! is_array($content)) {
                $content = [];
            }

            $localized_content[$language_code] = [
                'sorting' => [
                    'title'       => (string) Arr::get($content, 'sorting.title', ''),
                    'description' => (string) Arr::get($content, 'sorting.description', ''),
                ],
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
                    'code'       => (string) $item->code,
                    'is_enabled' => (bool) $item->is_enabled,
                    'sort_order' => (int) $item->sort_order,
                    'get'        => [
                        'key'   => (string) Arr::get($get_payload, 'key', ''),
                        'value' => Arr::get($get_payload, 'value'),
                        'extra' => $this->normalizeStringMap((array) Arr::get($get_payload, 'extra', [])),
                    ],
                    'config' => $this->normalizeItemConfigForForm($config_payload),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $localized_content
     */
    private function syncTranslationsFromForm(PageSetting $record, array $localized_content): void
    {
        foreach (new Language()->getActiveLanguages() as $language) {
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
                'is_enabled' => (bool) Arr::get($row, 'is_enabled', true),
                'sort_order' => (int) Arr::get($row, 'sort_order', 0),
                'get'        => [
                    'key'   => (string) Arr::get($row, 'get.key', ''),
                    'value' => Arr::get($row, 'get.value'),
                    'extra' => $this->resolvePersistedGetExtra(
                        form_row: $row,
                        item: $item,
                    ),
                ],
                'config' => $this->normalizeItemConfigFromForm((array) Arr::get($row, 'config', [])),
            ]);

            $item->save();
        }
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

    /**
     * @param  array<string, mixed>  $config_payload
     * @return array<string, mixed>
     */
    private function normalizeItemConfigForForm(array $config_payload): array
    {
        $normalized_config = array_filter($config_payload, function ($config_value) {
            return is_scalar($config_value) || $config_value === null;
        });

        $normalized_config['labels'] = $this->normalizeStringMap((array) Arr::get($config_payload, 'labels', []));

        return $normalized_config;
    }

    /**
     * @param  array<string, mixed>  $config_payload
     * @return array<string, mixed>
     */
    private function normalizeItemConfigFromForm(array $config_payload): array
    {
        $normalized_config = [];

        foreach ($config_payload as $config_key => $config_value) {
            if ($config_key === 'labels') {
                continue;
            }

            if (is_scalar($config_value) || $config_value === null) {
                $normalized_config[$config_key] = $config_value;
            }
        }

        $normalized_config['labels'] = $this->normalizeStringMap((array) Arr::get($config_payload, 'labels', []));

        return $normalized_config;
    }

    /**
     * Keep legacy `get.extra` payload stable when the field is no longer editable in admin.
     *
     * @param  array<string, mixed>  $form_row
     * @return array<string, string>
     */
    private function resolvePersistedGetExtra(array $form_row, PageSettingItem $item): array
    {
        $form_extra = Arr::get($form_row, 'get.extra');

        if (is_array($form_extra)) {
            return $this->normalizeStringMap($form_extra);
        }

        $item_get = is_array($item->get) ? $item->get : [];

        return $this->normalizeStringMap((array) Arr::get($item_get, 'extra', []));
    }
}
