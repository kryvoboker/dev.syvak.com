<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Products\Product;
use App\Supports\Services\Currency\ConvertPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductsTable
{
    use LanguageTrait;

    public static function configure(Table $table): Table
    {
        $current_language_id = self::getCurrentLanguageId();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load descriptions to avoid N+1 problem
                return $query->with([
                    'productDescription',
                    'productDiscount',
                    'productImage',
                    'productToAttribute',
                    'categories',
                    'slugs',
                ]);
            })
            ->columns([
                TextColumn::make('productDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Product $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $description = $record->productDescription
                            ->firstWhere('language_id', $current_language_id);

                        if (! $description) {
                            $description = $record->productDescription->first();
                        }

                        return $description?->name ?? '-';
                    }),

                TextColumn::make('model')
                    ->label(__('admin/default.columns.model'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('sku')
                    ->label(__('admin/default.columns.sku'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('ean')
                    ->label(__('admin/default.columns.ean'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('quantity')
                    ->label(__('admin/default.columns.quantity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('minimum')
                    ->label(__('admin/default.columns.minimum'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('image')
                    ->label(__('admin/default.columns.image'))
                    ->imageSize((int) config('app.images.product.preview_in_list_in_admin.width'))
                    ->checkFileExistence()
                    ->defaultImageUrl(Storage::url(config('app.images.product.no_image')))
                    ->extraImgAttributes([
                        'decoding' => 'async',
                        'loading'  => 'lazy',
                        'style'    => 'object-fit: contain;',
                    ]),

                TextColumn::make('price')
                    ->label(__('admin/default.columns.price'))
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Product $record) {
                        $discount = new Product()->getLastActualAndLastModifiedDiscountFromModel($record);

                        $currency      = config('app.currency.default_currency_code');
                        $exchange_rate = (float) config('app.currency.default_exchange_rate');

                        $convert_price = app(ConvertPrice::class);

                        if ($discount === null || $discount->price <= 0) {
                            return $convert_price->format($record->price, $currency, $exchange_rate);
                        }

                        $old_price = $convert_price->format($record->price, $currency, $exchange_rate);
                        $new_price = $convert_price->format($discount->price, $currency, $exchange_rate);

                        return '<span style="font-size: 1rem; text-decoration: line-through; color: rgb(156,163,175);"><del>' . $old_price . '</del></span><br>' .
                            '<span style="font-size: 1.3rem; color: rgb(239,68,68); font-weight: 600;">' . $new_price . '</span>';
                    }),

                TextColumn::make('viewed')
                    ->label(__('admin/default.columns.viewed'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_available')
                    ->label(__('admin/default.columns.date_available'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_added')
                    ->label(__('admin/default.columns.date_added'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('slugs.slug')
                    ->label(__('admin/default.columns.slug'))
                    ->searchable(['slug'])
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (Product $product) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $product->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    }),

                TextColumn::make('created_at')
                    ->label(__('admin/default.columns.created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('admin/default.columns.updated_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),

                Filter::make('name')
                    ->label(__('admin/default.filters.name'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/default.filters.name'))
                            ->placeholder(__('admin/default.placeholders.name'))
                            ->minLength(3)
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['name'] ?? null;
                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'productDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('name', 'LIKE', "%$search%");
                            },
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['name'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return null;
                        }

                        return __('admin/default.filters.name') . ': ' . Str::trim($search);
                    }),

                Filter::make('model')
                    ->label(__('admin/default.filters.model'))
                    ->schema([
                        TextInput::make('model')
                            ->label(__('admin/default.filters.model'))
                            ->placeholder(__('admin/default.placeholders.model'))
                            ->minLength(3)
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['model'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        return $query->whereLike('model', Str::trim($search) . '%');
                    }),

                Filter::make('sku')
                    ->label(__('admin/default.filters.sku'))
                    ->schema([
                        TextInput::make('sku')
                            ->label(__('admin/default.filters.sku'))
                            ->placeholder(__('admin/default.placeholders.sku'))
                            ->minLength(3)
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['sku'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        return $query->whereLike('sku', Str::trim($search) . '%');
                    }),

                Filter::make('ean')
                    ->label(__('admin/default.filters.ean'))
                    ->schema([
                        TextInput::make('ean')
                            ->label(__('admin/default.filters.ean'))
                            ->placeholder(__('admin/default.placeholders.ean'))
                            ->minLength(3)
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['ean'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        return $query->whereLike('ean', Str::trim($search) . '%');
                    }),

                Filter::make('price')
                    ->label(__('admin/default.filters.price'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin/default.filters.price'))
                            ->placeholder(__('admin/default.placeholders.price'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['price'] ?? null;

                        if (num_more_or_equal_num($search, 0) === false) {
                            return $query;
                        }

                        return $query->where('price', $search);
                    }),

                Filter::make('quantity')
                    ->label(__('admin/default.filters.quantity'))
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('admin/default.filters.quantity'))
                            ->placeholder(__('admin/default.placeholders.quantity'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['quantity'] ?? null;

                        if (num_more_or_equal_num($search, 0) === false) {
                            return $query;
                        }

                        return $query->where('quantity', $search);
                    }),

                Filter::make('category')
                    ->label(__('admin/default.filters.category'))
                    ->schema([
                        TextInput::make('category')
                            ->label(__('admin/default.filters.category'))
                            ->placeholder(__('admin/default.placeholders.category'))
                            ->minLength(3)
                            ->maxLength(255),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['category'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas('categories.categoryDescription', function (Builder $builder) use ($search): Builder {
                            return $builder->whereLike('name', "$search%");
                        });
                    }),

                Filter::make('slugs')
                    ->label(__('admin/default.filters.slug'))
                    ->schema([
                        TextInput::make('slugs')
                            ->label(__('admin/default.filters.slug'))
                            ->placeholder(__('admin/default.placeholders.slug'))
                            ->minLength(3)
                            ->maxLength(500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['slugs'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas('slugs', fn (Builder $builder): Builder => $builder->whereLike('slug', "$search%"));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
