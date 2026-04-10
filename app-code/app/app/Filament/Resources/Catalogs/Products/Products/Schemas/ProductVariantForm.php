<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Schemas;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Users\UserGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProductVariantTabs')
                    ->tabs([
                        Tab::make(__('admin/default.tabs.general'))
                            ->schema([
                                Section::make('Variant')
                                    ->schema([
                                        Toggle::make('is_default')
                                            ->label('Default variant')
                                            ->default(false)
                                            ->required(),

                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        TextInput::make('minimum')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),

                                        FileUpload::make('image')
                                            ->image()
                                            ->directory(resolve_upload_path_placeholders((string) config('app.images.product.image_path')))
                                            ->nullable(),

                                        DateTimePicker::make('date_available')
                                            ->default(now(config('app.timezone'))),

                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.translations'))
                            ->schema([
                                Section::make('Variant descriptions')
                                    ->schema([
                                        Repeater::make('descriptions')
                                            ->relationship('descriptions')
                                            ->schema([
                                                Select::make('language_id')
                                                    ->options(Language::query()->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('name')
                                                    ->maxLength(255)
                                                    ->nullable(),
                                                TextInput::make('meta_title')
                                                    ->maxLength(255)
                                                    ->nullable(),
                                                TextInput::make('meta_description')
                                                    ->maxLength(255)
                                                    ->nullable(),
                                                TextInput::make('meta_keywords')
                                                    ->maxLength(255)
                                                    ->nullable(),
                                                TextInput::make('description')
                                                    ->maxLength(5000)
                                                    ->nullable(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.images'))
                            ->schema([
                                Section::make('Variant images')
                                    ->schema([
                                        Repeater::make('images')
                                            ->relationship('images')
                                            ->schema([
                                                FileUpload::make('image')
                                                    ->image()
                                                    ->directory(resolve_upload_path_placeholders((string) config('app.images.product.image_path')))
                                                    ->required(),
                                                Toggle::make('is_primary')
                                                    ->default(false),
                                                TextInput::make('sort_order')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.discounts'))
                            ->schema([
                                Section::make('Variant discounts')
                                    ->schema([
                                        Repeater::make('discounts')
                                            ->relationship('discounts')
                                            ->schema([
                                                Select::make('user_group_id')
                                                    ->options(UserGroup::query()->where('is_active', true)->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('priority')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                                TextInput::make('price')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required(),
                                                DateTimePicker::make('date_start')
                                                    ->required(),
                                                DateTimePicker::make('date_end')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.attributes'))
                            ->schema([
                                Section::make('Variant attributes')
                                    ->schema([
                                        Repeater::make('attributeValues')
                                            ->relationship('attributeValues')
                                            ->schema([
                                                Select::make('attribute_id')
                                                    ->options(Attribute::query()->where('is_active', true)->pluck('id', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                Select::make('language_id')
                                                    ->options(Language::query()->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('value_string')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/default.tabs.slugs'))
                            ->schema([
                                Section::make('Variant slugs')
                                    ->schema([
                                        Repeater::make('slugs')
                                            ->relationship('slugs')
                                            ->schema([
                                                Select::make('language_id')
                                                    ->options(Language::query()->pluck('name', 'id')->all())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->maxLength(500),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
