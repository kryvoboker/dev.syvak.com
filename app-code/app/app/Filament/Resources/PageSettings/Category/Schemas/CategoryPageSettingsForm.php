<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Category\Schemas;

use App\Models\ApplicationSettings\Language;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
            $language_code = (string) $language->code;

            $language_tabs[] = Tabs\Tab::make($language->name)
                ->badge($language_code)
                ->schema([
                    TextInput::make("localized_content.$language_code.sorting.title")
                        ->label(__('admin/settings/category_page_settings.labels.sorting_title')),

                    Textarea::make("localized_content.$language_code.sorting.description")
                        ->label(__('admin/settings/category_page_settings.labels.sorting_description'))
                        ->rows(2),

                    TextInput::make("localized_content.$language_code.filters.title")
                        ->label(__('admin/settings/category_page_settings.labels.filters_title')),

                    Textarea::make("localized_content.$language_code.filters.description")
                        ->label(__('admin/settings/category_page_settings.labels.filters_description'))
                        ->rows(2),

                    TextInput::make("localized_content.$language_code.filters.drawer_title")
                        ->label(__('admin/settings/category_page_settings.labels.filters_drawer_title')),

                    TextInput::make("localized_content.$language_code.filters.apply_button_text")
                        ->label(__('admin/settings/category_page_settings.labels.filters_apply_button_text')),

                    TextInput::make("localized_content.$language_code.filters.clear_button_text")
                        ->label(__('admin/settings/category_page_settings.labels.filters_clear_button_text')),
                ])
                ->columns(1);
        }

        return $schema
            ->components([
                Tabs::make('CategoryPageSettingsTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.sorting'))
                            ->schema([
                                Toggle::make('is_sorting_enabled')
                                    ->label(__('admin/settings/category_page_settings.labels.is_sorting_enabled'))
                                    ->default(true),

                                Repeater::make('sorting_items')
                                    ->label(__('admin/settings/category_page_settings.labels.sorting_items'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('admin/settings/category_page_settings.labels.code'))
                                            ->disabled()
                                            ->dehydrated(),

                                        Toggle::make('is_enabled')
                                            ->label(__('admin/settings/category_page_settings.labels.is_enabled'))
                                            ->default(true),

                                        TextInput::make('sort_order')
                                            ->label(__('admin/settings/category_page_settings.labels.sort_order'))
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('get.key')
                                            ->label(__('admin/settings/category_page_settings.labels.get_key'))
                                            ->required(),

                                        TextInput::make('get.value')
                                            ->label(__('admin/settings/category_page_settings.labels.get_value')),

                                        KeyValue::make('get.extra')
                                            ->label(__('admin/settings/category_page_settings.labels.get_extra'))
                                            ->keyLabel(__('admin/settings/category_page_settings.labels.key'))
                                            ->valueLabel(__('admin/settings/category_page_settings.labels.value')),

                                        TextInput::make('config.selection')
                                            ->label(__('admin/settings/category_page_settings.labels.selection_mode')),

                                        Section::make(__('admin/settings/category_page_settings.labels.option_labels'))
                                            ->schema([
                                                self::buildOptionLabelTabs(
                                                    $active_languages,
                                                    'sorting_item_option_labels_tabs',
                                                ),
                                            ]),
                                    ])
                                    ->defaultItems(0)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->columns(),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/category_page_settings.tabs.filters'))
                            ->schema([
                                Toggle::make('is_filtering_enabled')
                                    ->label(__('admin/settings/category_page_settings.labels.is_filtering_enabled'))
                                    ->default(true),

                                Repeater::make('filter_items')
                                    ->label(__('admin/settings/category_page_settings.labels.filter_items'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('admin/settings/category_page_settings.labels.code'))
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('source_type')
                                            ->label(__('admin/settings/category_page_settings.labels.source_type'))
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('source_id')
                                            ->label(__('admin/settings/category_page_settings.labels.source_id'))
                                            ->disabled()
                                            ->dehydrated(),

                                        Toggle::make('is_enabled')
                                            ->label(__('admin/settings/category_page_settings.labels.is_enabled'))
                                            ->default(true),

                                        TextInput::make('sort_order')
                                            ->label(__('admin/settings/category_page_settings.labels.sort_order'))
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('get.key')
                                            ->label(__('admin/settings/category_page_settings.labels.get_key'))
                                            ->required(),

                                        TextInput::make('get.value')
                                            ->label(__('admin/settings/category_page_settings.labels.get_value')),

                                        KeyValue::make('get.extra')
                                            ->label(__('admin/settings/category_page_settings.labels.get_extra'))
                                            ->keyLabel(__('admin/settings/category_page_settings.labels.key'))
                                            ->valueLabel(__('admin/settings/category_page_settings.labels.value')),

                                        TextInput::make('config.mode')
                                            ->label(__('admin/settings/category_page_settings.labels.filter_mode')),

                                        TextInput::make('config.min_price')
                                            ->numeric()
                                            ->label(__('admin/settings/category_page_settings.labels.min_price')),

                                        TextInput::make('config.max_price')
                                            ->numeric()
                                            ->label(__('admin/settings/category_page_settings.labels.max_price')),

                                        TextInput::make('config.step')
                                            ->numeric()
                                            ->label(__('admin/settings/category_page_settings.labels.step')),

                                        Section::make(__('admin/settings/category_page_settings.labels.option_labels'))
                                            ->schema([
                                                self::buildOptionLabelTabs(
                                                    $active_languages,
                                                    'filter_item_option_labels_tabs',
                                                ),
                                            ]),
                                    ])
                                    ->defaultItems(0)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->collapsed(false)
                                    ->columns(),
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
            $language_code = (string) $language->code;

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
