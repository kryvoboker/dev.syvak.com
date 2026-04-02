<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSettings\Product\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ProductPageSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProductPageSettingsTabs')
                    ->tabs([
                        Tabs\Tab::make(__('admin/settings/product_page_settings.tabs.for_customer'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('minimum_stock_quantity')
                                            ->label(__('admin/settings/product_page_settings.labels.minimum_stock_quantity'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        Grid::make()
                                            ->schema([
                                                TextInput::make('product_image_width')
                                                    ->label(__('admin/settings/product_page_settings.labels.product_image_width'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('product_image_height')
                                                    ->label(__('admin/settings/product_page_settings.labels.product_image_height'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),
                                    ]),
                            ])
                            ->columns(1),

                        Tabs\Tab::make(__('admin/settings/product_page_settings.tabs.for_admin'))
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextInput::make('ean_max_length')
                                                    ->label(__('admin/settings/product_page_settings.labels.ean_max_length'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('upload_max_size_mb')
                                                    ->label(__('admin/settings/product_page_settings.labels.upload_max_size_mb'))
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),
                                    ]),

                                Section::make()
                                    ->schema([
                                        TextInput::make('image_upload_directory')
                                            ->label(__('admin/settings/product_page_settings.labels.image_upload_directory'))
                                            ->helperText(__('admin/settings/product_page_settings.helpers.image_upload_directory'))
                                            ->placeholder('images/products/{year}/{month}')
                                            ->rules(['required', 'string', 'max:255'])
                                            ->required(),

                                        Grid::make()
                                            ->schema([
                                                FileUpload::make('no_image_path')
                                                    ->label(__('admin/settings/product_page_settings.labels.no_image_path'))
                                                    ->helperText(__('admin/settings/product_page_settings.helpers.no_image_path'))
                                                    ->image()
                                                    ->directory('images')
                                                    ->visibility('public')
                                                    ->imageEditor()
                                                    ->required(),

                                                Grid::make()
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('preview_list_image_width')
                                                            ->label(__('admin/settings/product_page_settings.labels.preview_list_image_width'))
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),

                                                        TextInput::make('preview_list_image_height')
                                                            ->label(__('admin/settings/product_page_settings.labels.preview_list_image_height'))
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),

                                                        TextInput::make('preview_page_image_width')
                                                            ->label(__('admin/settings/product_page_settings.labels.preview_page_image_width'))
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),

                                                        TextInput::make('preview_page_image_height')
                                                            ->label(__('admin/settings/product_page_settings.labels.preview_page_image_height'))
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->required(),
                                                    ]),
                                            ]),
                                    ]),
                            ])
                            ->columns(1),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
