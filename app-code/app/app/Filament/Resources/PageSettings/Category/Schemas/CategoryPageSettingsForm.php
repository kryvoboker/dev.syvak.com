<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Schemas;

use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class CategoryPageSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        $active_languages = new Language()->getActiveLanguages();
        $language_tabs    = [];

        foreach ($active_languages as $language) {
            $language_code = (string)$language->code;

            $language_tabs[] = Tabs\Tab::make($language->name)
                ->badge($language_code)
                ->schema([
                    TextInput::make("localized_content.$language_code.sorting.title")
                        ->label(__('admin/settings/category_page_settings.labels.sorting_title')),

                    Textarea::make("localized_content.$language_code.sorting.description")
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
                                            ->default((int)config('app.page_settings.category.products_per_page_limit', 20)),

                                        Toggle::make('is_ajax_products_loading_enabled')
                                            ->label(__('admin/settings/category_page_settings.labels.is_ajax_products_loading_enabled'))
                                            ->default((bool)config('app.page_settings.category.ajax_products_loading_enabled', true)),
                                    ]),

                                Section::make()
                                    ->schema([
                                        TextInput::make('product_image_width')
                                            ->label(__('admin/settings/category_page_settings.labels.product_image_width'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->default((int)config('app.page_settings.category.product_image_width', 420))
                                            ->columns(1),

                                        TextInput::make('product_image_height')
                                            ->label(__('admin/settings/category_page_settings.labels.product_image_height'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->default((int)config('app.page_settings.category.product_image_height', 420))
                                            ->columns(1),
                                    ])
                                    ->columns(),
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
                                                TextInput::make('code')
                                                    ->label(__('admin/settings/category_page_settings.labels.code'))
                                                    ->disabled()
                                                    ->dehydrated(),

                                                TextInput::make('get.key')
                                                    ->label(__('admin/settings/category_page_settings.labels.get_key'))
                                                    ->disabled()
                                                    ->required(),

                                                TextInput::make('get.value')
                                                    ->label(__('admin/settings/category_page_settings.labels.get_value'))
                                                    ->disabled(),
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

    private static function buildOptionLabelTabs(
        $active_languages,
        string $tabs_name,
    ): Tabs {
        $tabs = [];

        foreach ($active_languages as $language) {
            $language_code = (string)$language->code;

            $tabs[] = Tabs\Tab::make($language->name)
                ->badge($language_code)
                ->schema([
                    TextInput::make("config.labels.$language_code")
                        ->label(__('admin/settings/category_page_settings.labels.option_label_value')),
                ]);
        }

        return Tabs::make($tabs_name)
            ->tabs($tabs)
            ->activeTab(1)
            ->contained(false);
    }
}
