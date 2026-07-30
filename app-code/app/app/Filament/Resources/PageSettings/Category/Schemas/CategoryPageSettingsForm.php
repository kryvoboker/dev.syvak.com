<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Schemas;

use App\Models\ApplicationSettings\Language;
use App\Services\PageSettings\HeaderCategoryService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryPageSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = (new Language())->getActiveLanguages();
        $language_tabs = [];

        foreach ($active_languages as $language) {
            $language_id = (string) $language->id;

            $language_tabs[] = Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    TextInput::make("localized_content.$language_id.sorting.title")
                        ->label(__('admin/settings/category_page_settings.labels.sorting_title')),

                    Textarea::make("localized_content.$language_id.sorting.description")
                        ->label(__('admin/settings/category_page_settings.labels.sorting_description'))
                        ->rows(2),
                ])
                ->columns(1);
        }

        return $schema
            ->components([
                Tabs::make('CategoryPageSettingsTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.general'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('products_per_page_limit')
                                            ->label(__('admin/settings/category_page_settings.labels.products_per_page_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->default((int) config('app.page_settings.category.products_per_page_limit', 20)),

                                        Toggle::make('is_ajax_products_loading_enabled')
                                            ->label(__('admin/settings/category_page_settings.labels.is_ajax_products_loading_enabled'))
                                            ->default((bool) config('app.page_settings.category.ajax_products_loading_enabled', true)),
                                    ]),

                                Section::make()
                                    ->schema([
                                        TextInput::make('product_image_width')
                                            ->label(__('admin/settings/category_page_settings.labels.product_image_width'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->default((int) config('app.page_settings.category.product_image_width', 420))
                                            ->columns(1),

                                        TextInput::make('product_image_height')
                                            ->label(__('admin/settings/category_page_settings.labels.product_image_height'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->default((int) config('app.page_settings.category.product_image_height', 420))
                                            ->columns(1),
                                    ])
                                    ->columns(),

                                Section::make(__('admin/settings/category_page_settings.sections.header_categories'))
                                    ->schema([
                                        Repeater::make('header_categories')
                                            ->label(__('admin/settings/category_page_settings.labels.header_categories'))
                                            ->helperText(__('admin/settings/category_page_settings.helpers.header_categories'))
                                            ->schema([
                                                Select::make('category_id')
                                                    ->label(__('admin/settings/category_page_settings.labels.header_category'))
                                                    ->placeholder(__('admin/settings/category_page_settings.placeholders.header_category'))
                                                    ->searchable()
                                                    ->searchDebounce(1000)
                                                    ->getSearchResultsUsing(function (Get $get, string $search): array {
                                                        if (Str::length($search) < 3) {
                                                            Notification::make()
                                                                ->title(__('admin/default.errors.title'))
                                                                ->body(__('admin/default.errors.min_search_length', [
                                                                    'length' => 3,
                                                                ]))
                                                                ->danger()
                                                                ->send();

                                                            return [];
                                                        }

                                                        return app(HeaderCategoryService::class)->searchOptions(
                                                            $search,
                                                            self::resolveSelectedHeaderCategoryIds($get),
                                                        );
                                                    })
                                                    ->getOptionLabelUsing(fn (int|string|null $value): ?string => app(HeaderCategoryService::class)->getOptionLabel($value))
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->required()
                                                    ->rules(['integer', 'distinct'])
                                                    ->native(false),
                                            ])
                                            ->defaultItems(0)
                                            ->addActionLabel(__('admin/settings/category_page_settings.actions.add_header_category'))
                                            ->reorderable()
                                            ->deletable()
                                            ->columns(1),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.for_admin'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('category_upload_max_size_mb')
                                            ->label(__('admin/settings/category_page_settings.labels.category_upload_max_size_mb'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->columns(1),

                                        TextInput::make('category_image_upload_directory')
                                            ->label(__('admin/settings/category_page_settings.labels.category_image_upload_directory'))
                                            ->helperText(__('admin/settings/category_page_settings.helpers.category_image_upload_directory'))
                                            ->placeholder('images/categories/{year}/{month}')
                                            ->rules(['required', 'string', 'max:255'])
                                            ->required()
                                            ->columns(1),
                                    ])
                                    ->columns(),

                                Section::make()
                                    ->schema([
                                        FileUpload::make('category_no_image_path')
                                            ->label(__('admin/settings/category_page_settings.labels.category_no_image_path'))
                                            ->helperText(__('admin/settings/category_page_settings.helpers.category_no_image_path'))
                                            ->image()
                                            ->directory('images')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->required(),

                                        Grid::make()
                                            ->columns(1)
                                            ->schema([
                                                TextInput::make('category_preview_list_width')
                                                    ->label(__('admin/settings/category_page_settings.labels.category_preview_list_width'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('category_preview_list_height')
                                                    ->label(__('admin/settings/category_page_settings.labels.category_preview_list_height'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('category_preview_page_width')
                                                    ->label(__('admin/settings/category_page_settings.labels.category_preview_page_width'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('category_preview_page_height')
                                                    ->label(__('admin/settings/category_page_settings.labels.category_preview_page_height'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),
                                    ])
                                    ->columns(),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.sorting'))
                            ->schema([
                                Toggle::make('is_sorting_enabled')
                                    ->label(__('admin/settings/category_page_settings.labels.is_sorting_enabled'))
                                    ->default(true),

                                Repeater::make('sorting_items')
                                    ->label(__('admin/settings/category_page_settings.labels.sorting_items'))
                                    ->schema([
                                        Section::make(__('admin/settings/category_page_settings.labels.option_labels'))
                                            ->schema([
                                                self::buildOptionLabelTabs(
                                                    $active_languages,
                                                    'sorting_item_option_labels_tabs',
                                                ),
                                            ]),

                                        Section::make()
                                            ->schema([
                                                Select::make('code')
                                                    ->label(__('admin/settings/category_page_settings.labels.code'))
                                                    ->options(self::resolveSortCodeOptions())
                                                    ->in(self::resolveSortCodeAllowedValues())
                                                    ->disabled(fn (mixed $state): bool => self::shouldLockFixedField($state, self::resolveSortCodeAllowedValues()))
                                                    ->dehydrated()
                                                    ->required()
                                                    ->native(false),

                                                Select::make('get.key')
                                                    ->label(__('admin/settings/category_page_settings.labels.get_key'))
                                                    ->options(self::resolveSortGetKeyOptions())
                                                    ->in(self::resolveSortGetKeyAllowedValues())
                                                    ->disabled(fn (mixed $state): bool => self::shouldLockFixedField($state, self::resolveSortGetKeyAllowedValues()))
                                                    ->dehydrated()
                                                    ->required()
                                                    ->native(false),

                                                Select::make('get.value')
                                                    ->label(__('admin/settings/category_page_settings.labels.get_value'))
                                                    ->options(self::resolveSortGetValueOptions())
                                                    ->in(self::resolveSortGetValueAllowedValues())
                                                    ->disabled(fn (mixed $state): bool => self::shouldLockFixedField($state, self::resolveSortGetValueAllowedValues()))
                                                    ->dehydrated()
                                                    ->required()
                                                    ->native(false),
                                            ])
                                            ->columns(3),

                                        TextInput::make('sort_order')
                                            ->label(__('admin/settings/category_page_settings.labels.sort_order'))
                                            ->numeric()
                                            ->required(),

                                        Toggle::make('is_enabled')
                                            ->label(__('admin/settings/category_page_settings.labels.is_enabled'))
                                            ->default(true),
                                    ])
                                    ->defaultItems(0)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->columns(1),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.localized_content'))
                            ->schema([
                                Tabs::make('CategoryPageSettingsLanguageTabs')
                                    ->tabs($language_tabs)
                                    ->activeTab(1)
                                    ->contained(false),
                            ])
                            ->columns(1),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param Collection<int, Language> $active_languages
     * @param string     $tabs_name
     *
     * @return Tabs
     */
    private static function buildOptionLabelTabs(
        Collection $active_languages,
        string $tabs_name,
    ): Tabs {
        $tabs = [];

        foreach ($active_languages as $language) {
            $language_id = (string) $language->id;

            $tabs[] = Tabs\Tab::make($language->name)
                ->badge((string) $language->code)
                ->schema([
                    TextInput::make("config.labels.$language_id")
                        ->label(__('admin/settings/category_page_settings.labels.option_label_value')),
                ]);
        }

        return Tabs::make($tabs_name)
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }

    /**
     * @return array<string, string>
     */
    private static function resolveSortCodeOptions(): array
    {
        return self::resolveSelectOptionsFromConfig('page-settings.sort_codes');
    }

    /**
     * @return array<string, string>
     */
    private static function resolveSortGetKeyOptions(): array
    {
        return self::resolveSelectOptionsFromConfig('page-settings.sort_get_keys');
    }

    /**
     * @return array<string, string>
     */
    private static function resolveSortGetValueOptions(): array
    {
        return self::resolveSelectOptionsFromConfig('page-settings.sort_get_values');
    }

    /**
     * @return array<int, string>
     */
    private static function resolveSortCodeAllowedValues(): array
    {
        return self::resolveAllowedValuesFromConfig('page-settings.sort_codes');
    }

    /**
     * @return array<int, string>
     */
    private static function resolveSortGetKeyAllowedValues(): array
    {
        return self::resolveAllowedValuesFromConfig('page-settings.sort_get_keys');
    }

    /**
     * @return array<int, string>
     */
    private static function resolveSortGetValueAllowedValues(): array
    {
        return self::resolveAllowedValuesFromConfig('page-settings.sort_get_values');
    }

    /**
     * @return array<string, string>
     */
    private static function resolveSelectOptionsFromConfig(string $config_key): array
    {
        return collect((array) config($config_key, []))
            ->mapWithKeys(function (mixed $value, mixed $key): array {
                $label = (string) (is_string($key) ? $key : $value);

                return [
                    (string) $value => $label,
                ];
            })
            ->filter(fn (mixed $label, mixed $value): bool => filled((string) $value) && filled((string) $label))
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private static function resolveSelectedHeaderCategoryIds(Get $get): array
    {
        $header_categories = $get('../../header_categories');

        if (! is_array($header_categories)) {
            return [];
        }

        return app(HeaderCategoryService::class)->normalizeCategoryIds(
            collect($header_categories)
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): mixed => $item['category_id'] ?? null)
                ->all(),
        );
    }

    /**
     * @return array<int, string>
     */
    private static function resolveAllowedValuesFromConfig(string $config_key): array
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

    /**
     * Fixed contract fields must stay locked, but we keep them editable when value is unexpectedly empty.
     *
     * @param  array<int, string>  $allowed_values
     */
    private static function shouldLockFixedField(mixed $state, array $allowed_values): bool
    {
        if (! is_string($state) || blank($state)) {
            return false;
        }

        return in_array($state, $allowed_values, true);
    }
}
