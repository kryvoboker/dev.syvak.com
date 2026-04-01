<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Search\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class SearchPageSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('SearchPageSettingsTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/settings/search_page_settings.tabs.general'))
                            ->schema([
                                TextInput::make('products_per_page_limit')
                                    ->label(__('admin/settings/search_page_settings.labels.products_per_page_limit'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),

                                Section::make(__('admin/settings/search_page_settings.sections.search_product_image'))
                                    ->schema([
                                        TextInput::make('search_product_image_width')
                                            ->label(__('admin/settings/search_page_settings.labels.search_product_image_width'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('search_product_image_height')
                                            ->label(__('admin/settings/search_page_settings.labels.search_product_image_height'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),
                                    ])
                                    ->columns(1),

                                Section::make(__('admin/settings/search_page_settings.sections.search_not_found_image'))
                                    ->schema([
                                        FileUpload::make('search_not_found_image_path')
                                            ->label(__('admin/settings/search_page_settings.labels.search_not_found_image_path'))
                                            ->image()
                                            ->directory('images/search')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->required(),

                                        TextInput::make('search_not_found_image_width')
                                            ->label(__('admin/settings/search_page_settings.labels.search_not_found_image_width'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('search_not_found_image_height')
                                            ->label(__('admin/settings/search_page_settings.labels.search_not_found_image_height'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(1),
                    ])
                    ->activeTab(1)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
