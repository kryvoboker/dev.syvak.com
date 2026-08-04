<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Pages;

use App\Filament\Pages\Wiki\CategoryPageSettingsWikiPage;
use App\Filament\Resources\PageSettings\Category\CategoryPageSettingResource;
use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\HeaderCategoryService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
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
            'admin/page-settings' => __('admin/default.menu.item_page_settings'),
            CategoryPageSettingResource::getUrl('index') => __('admin/settings/category_page_settings.navigation_label'),
        ];
    }

    /**
     * @throws Throwable
     */
    public function mount(int|string|null $record = null): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapCategoryPageSetting();

        parent::mount($page_setting->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            Action::make('open_wiki')
                ->label(__('admin/settings/category_page_settings.actions.open_wiki'))
                ->icon(Heroicon::Document)
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
        $record_settings = is_array($record->settings) ? $record->settings : [];

        $data['is_sorting_enabled'] = (bool) Arr::get($record_settings, 'ui.sorting.enabled', true);
        $data['localized_content'] = $this->mapLocalizedContentForForm($record_settings);
        $data['sorting_items'] = $this->mapSortingItemsForForm($record_settings);
        $data['header_categories'] = $this->mapHeaderCategoriesForForm($record_settings);
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
            'settings' => $this->normalizeSettingsContract($record, $data),
        ]);

        return $record->fresh() ?? $record;
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
                'meta' => ['contract_version' => 2],
                'ui' => [
                    'sorting' => ['enabled' => true],
                    'filtering' => ['enabled' => false],
                ],
                'pagination' => [
                    'products_per_page_limit' => (int) config('app.page_settings.category.products_per_page_limit', 20),
                    'ajax_products_loading_enabled' => (bool) config('app.page_settings.category.ajax_products_loading_enabled', true),
                ],
                'images' => [
                    'products' => [
                        'width' => (int) config('app.page_settings.category.product_image_width', 420),
                        'height' => (int) config('app.page_settings.category.product_image_height', 420),
                    ],
                ],
                'admin' => [
                    'upload' => [
                        'max_size_kb' => (int) config('app.images.category.upload.max_size_kb', 5120),
                        'directory' => normalize_upload_path_template((string) config('app.images.category.image_path', 'images/categories/' . date('Y/m'))),
                    ],
                    'images' => [
                        'no_image' => [
                            'path' => (string) config('app.images.category.no_image', 'images/no-image.png'),
                        ],
                        'preview_in_list' => [
                            'width' => (int) config('app.images.category.preview_in_list_in_admin.width', 100),
                            'height' => (int) config('app.images.category.preview_in_list_in_admin.height', 100),
                        ],
                        'preview_in_page' => [
                            'width' => (int) config('app.images.category.preview_in_page_in_admin.width', 500),
                            'height' => (int) config('app.images.category.preview_in_page_in_admin.height', 500),
                        ],
                    ],
                ],
                'items' => [
                    'sorting' => [],
                    'filters' => (array) Arr::get($settings, 'items.filters', []),
                ],
                'localized' => [],
            ],
            $settings,
        );

        Arr::set($settings, 'meta.contract_version', 2);
        Arr::set(
            $settings,
            'header.categories',
            app(HeaderCategoryService::class)->normalizeActiveCategoryIds(
                collect((array) Arr::get($data, 'header_categories', []))
                    ->filter(fn (mixed $item): bool => is_array($item))
                    ->map(fn (array $item): mixed => $item['category_id'] ?? null)
                    ->all(),
            ),
        );
        Arr::set($settings, 'ui.sorting.enabled', (bool) Arr::get($data, 'is_sorting_enabled', true));
        Arr::set($settings, 'ui.filtering.enabled', (bool) Arr::get($settings, 'ui.filtering.enabled', false));
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
        Arr::set(
            $settings,
            'items.sorting',
            $this->normalizeSortingItemsFromForm(
                rows: (array) Arr::get($data, 'sorting_items', []),
                persisted_rows: (array) Arr::get($settings, 'items.sorting', []),
            ),
        );
        Arr::set($settings, 'localized', $this->normalizeLocalizedContentFromForm((array) Arr::get($data, 'localized_content', [])));

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    private function mapLocalizedContentForForm(array $settings): array
    {
        $localized_data = Arr::get($settings, 'localized', []);
        $localized_content = [];

        if (! is_array($localized_data)) {
            $localized_data = [];
        }

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_id = (string) $language->id;
            $content = Arr::get($localized_data, $language_id, []);

            if (! is_array($content)) {
                $content = [];
            }

            $localized_content[$language_id] = [
                'sorting' => [
                    'title' => (string) Arr::get($content, 'sorting.title', ''),
                    'description' => (string) Arr::get($content, 'sorting.description', ''),
                ],
            ];
        }

        return $localized_content;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    private function mapSortingItemsForForm(array $settings): array
    {
        return collect((array) Arr::get($settings, 'items.sorting', []))
            ->filter(fn (mixed $item): bool => is_array($item) && filled((string) Arr::get($item, 'code')))
            ->map(function (array $item): array {
                $get_payload = is_array(Arr::get($item, 'get')) ? Arr::get($item, 'get') : [];
                $config_payload = is_array(Arr::get($item, 'config')) ? Arr::get($item, 'config') : [];

                return [
                    'code' => (string) Arr::get($item, 'code', ''),
                    'is_enabled' => (bool) Arr::get($item, 'is_enabled', true),
                    'sort_order' => (int) Arr::get($item, 'sort_order', 0),
                    'get' => [
                        'key' => (string) Arr::get($get_payload, 'key', ''),
                        'value' => Arr::get($get_payload, 'value'),
                        'extra' => $this->normalizeStringMap((array) Arr::get($get_payload, 'extra', [])),
                    ],
                    'config' => $this->normalizeItemConfigForForm($config_payload),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, array{category_id:int}>
     */
    private function mapHeaderCategoriesForForm(array $settings): array
    {
        $category_ids = app(HeaderCategoryService::class)->normalizeActiveCategoryIds(
            Arr::get($settings, 'header.categories', []),
        );

        return array_map(
            fn (int $category_id): array => ['category_id' => $category_id],
            $category_ids,
        );
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
    private function normalizeItemConfigFromForm(array $config_payload, array $persisted_config = []): array
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

        $persisted_labels = $this->normalizeStringMap((array) Arr::get($persisted_config, 'labels', []));
        $submitted_labels = $this->normalizeStringMap((array) Arr::get($config_payload, 'labels', []));

        /**
         * Keep labels from DB for locales that are absent in the current admin form
         * and override only locales explicitly submitted by user.
         */
        $normalized_config['labels'] = array_replace($persisted_labels, $submitted_labels);

        return $normalized_config;
    }

    /**
     * Keep legacy `get.extra` payload stable when the field is no longer editable in admin.
     *
     * @param  array<string, mixed>  $form_row
     * @param  array<string, mixed>  $persisted_row
     * @return array<string, string>
     */
    private function resolvePersistedGetExtra(array $form_row, array $persisted_row): array
    {
        $form_extra = Arr::get($form_row, 'get.extra');

        if (is_array($form_extra)) {
            return $this->normalizeStringMap($form_extra);
        }

        $persisted_get = is_array(Arr::get($persisted_row, 'get')) ? Arr::get($persisted_row, 'get') : [];

        return $this->normalizeStringMap((array) Arr::get($persisted_get, 'extra', []));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $persisted_rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSortingItemsFromForm(array $rows, array $persisted_rows): array
    {
        $persisted_by_code = collect($persisted_rows)
            ->filter(fn (array $row): bool => filled((string) Arr::get($row, 'code')))
            ->keyBy(fn (array $row): string => (string) Arr::get($row, 'code'));

        $allowed_sort_codes = $this->resolveAllowedSortCodes();
        $allowed_sort_get_keys = $this->resolveAllowedSortGetKeys();
        $allowed_sort_get_values = $this->resolveAllowedSortGetValues();

        return collect($rows)
            ->values()
            ->map(function (array $row, int $index) use ($persisted_by_code, $persisted_rows, $allowed_sort_codes, $allowed_sort_get_keys, $allowed_sort_get_values): ?array {
                $indexed_persisted_row = (array) Arr::get($persisted_rows, $index, []);
                $incoming_code = $this->resolveSortingSelectValue(
                    value: Arr::get($row, 'code'),
                    allowed_values: $allowed_sort_codes,
                    fallback: (string) Arr::get($indexed_persisted_row, 'code', ''),
                );

                $persisted_row = $incoming_code !== null
                    ? (array) $persisted_by_code->get($incoming_code, $indexed_persisted_row)
                    : $indexed_persisted_row;
                $code = $this->resolveSortingSelectValue(
                    value: $incoming_code,
                    allowed_values: $allowed_sort_codes,
                    fallback: (string) Arr::get($persisted_row, 'code', ''),
                );

                if (blank($code)) {
                    return null;
                }

                $persisted_get = is_array(Arr::get($persisted_row, 'get')) ? Arr::get($persisted_row, 'get') : [];
                $resolved_key = $this->resolveSortingSelectValue(
                    value: Arr::get($row, 'get.key'),
                    allowed_values: $allowed_sort_get_keys,
                    fallback: (string) Arr::get($persisted_get, 'key', ''),
                );
                $resolved_value = $this->resolveSortingSelectValue(
                    value: Arr::get($row, 'get.value'),
                    allowed_values: $allowed_sort_get_values,
                    fallback: (string) Arr::get($persisted_get, 'value', ''),
                );

                $persisted_config = is_array(Arr::get($persisted_row, 'config')) ? Arr::get($persisted_row, 'config') : [];

                return [
                    'code' => $code,
                    'is_enabled' => (bool) Arr::get($row, 'is_enabled', true),
                    'sort_order' => max(0, (int) Arr::get($row, 'sort_order', 0)),
                    'source_type' => Arr::get($persisted_row, 'source_type', 'static'),
                    'source_id' => Arr::get($persisted_row, 'source_id'),
                    'get' => [
                        'key' => $resolved_key,
                        'value' => $resolved_value,
                        'extra' => $this->resolvePersistedGetExtra(
                            form_row: $row,
                            persisted_row: $persisted_row,
                        ),
                    ],
                    'config' => $this->normalizeItemConfigFromForm(
                        config_payload : (array) Arr::get($row, 'config', []),
                        persisted_config: $persisted_config,
                    ),
                ];
            })
            ->filter(fn (mixed $row): bool => is_array($row))
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveAllowedSortCodes(): array
    {
        return $this->resolveAllowedValuesFromConfig('page-settings.sort_codes');
    }

    /**
     * @return array<int, string>
     */
    private function resolveAllowedSortGetKeys(): array
    {
        return $this->resolveAllowedValuesFromConfig('page-settings.sort_get_keys');
    }

    /**
     * @return array<int, string>
     */
    private function resolveAllowedSortGetValues(): array
    {
        return $this->resolveAllowedValuesFromConfig('page-settings.sort_get_values');
    }

    /**
     * @return array<int, string>
     */
    private function resolveAllowedValuesFromConfig(string $config_key): array
    {
        return collect((array) config($config_key, []))
            ->flatMap(function (mixed $value, mixed $key): array {
                $normalized = [];

                if (is_string($key) && filled($key)) {
                    $normalized[] = $key;
                }

                $string_value = (string) $value;

                if (filled($string_value)) {
                    $normalized[] = $string_value;
                }

                return $normalized;
            })
            ->filter(fn (mixed $value): bool => filled((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveSortingSelectValue(
        mixed $value,
        array $allowed_values,
        string $fallback = '',
    ): ?string {
        $resolved_value = is_string($value) ? trim($value) : '';
        $fallback = trim($fallback);

        if ($resolved_value !== '' && in_array($resolved_value, $allowed_values, true)) {
            return $resolved_value;
        }

        if ($fallback !== '' && in_array($fallback, $allowed_values, true)) {
            return $fallback;
        }

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * @param  array<string, mixed>  $localized_content
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLocalizedContentFromForm(array $localized_content): array
    {
        $normalized_localized_content = [];

        foreach ((new Language())->getActiveLanguages() as $language) {
            $language_id = (string) $language->id;
            $language_content = Arr::get($localized_content, $language_id, []);

            $normalized_localized_content[$language_id] = [
                'sorting' => [
                    'title' => (string) Arr::get($language_content, 'sorting.title', ''),
                    'description' => (string) Arr::get($language_content, 'sorting.description', ''),
                ],
            ];
        }

        return $normalized_localized_content;
    }
}
