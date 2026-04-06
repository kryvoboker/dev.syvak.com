<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\RelationManagers;

use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Catalogs\Products\ProductVariant;
use App\Models\Users\UserGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->columns(2),

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
                    ]),

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
                    ]),

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
                    ]),

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
                    ]),

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
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->sortable(),
                IconColumn::make('is_default')->boolean()->label('Default'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('price')->numeric(2)->sortable(),
                ImageColumn::make('image'),
                TextColumn::make('sort_order')->numeric()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->enforceSingleDefaultVariant($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->enforceSingleDefaultVariant($data);
    }

    /**
     * Keep exactly one default variant per product in admin workflows.
     */
    private function enforceSingleDefaultVariant(array $data): array
    {
        if (! ((bool) ($data['is_default'] ?? false))) {
            return $data;
        }

        ProductVariant::query()
            ->where('product_id', (int) data_get($this->getOwnerRecord(), 'id'))
            ->where('is_default', true)
            ->update(['is_default' => false]);

        return $data;
    }
}
