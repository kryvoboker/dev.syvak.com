<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Tables;

use App\Models\Catalogs\Products\Product;
use App\Models\Settings\Language;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NumberFormatter;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('productDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function ($record) {
                        $language = new Language();

                        // Get current locale language ID (adjust based on your logic)
                        $current_language_id = $language->getLanguageByCode(app()->getLocale())?->id;

                        if ($current_language_id === null) {
                            $current_language_id = $language->getDefaultLanguage()?->id;
                        }

                        if ($current_language_id === null) {
                            Notification::make()
                                ->title(__('admin/default.errors.title'))
                                ->body(__('admin/default.errors.no_language'))
                                ->danger()
                                ->send();

                            return '-';
                        }

                        $description = $record->productDescription
                            ->firstWhere('language_id', $current_language_id);

                        if (!$description) {
                            $description = $record->productDescription->first();
                        }

                        return $description?->name ?? '-';
                    }),

                TextColumn::make('model')
                    ->label(__('admin/default.columns.model'))
                    ->searchable(),

                TextColumn::make('sku')
                    ->label(__('admin/default.columns.sku'))
                    ->searchable(),

                TextColumn::make('ean')
                    ->label(__('admin/default.columns.ean'))
                    ->searchable()
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
                    ->imageSize((int)config('app.images.product.preview_in_list_in_admin.width'))
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
                    /*->money(
                        currency     : config('app.currency.default_currency'),
                        locale       : config('app.currency.default_format_locale'),
                        decimalPlaces: (int)config('app.currency.default_decimal_places'),
                    )*/
                    ->getStateUsing(function (Product $record) {
                        $discount = new Product()->getLastActualAndLastModifiedDiscountFromModel($record);

                        $currency = config('app.currency.default_currency_code');

                        $number_formatter = new NumberFormatter(
                            config('app.currency.default_format_locale'),
                            NumberFormatter::CURRENCY
                        );

                        $number_formatter->setAttribute(
                            NumberFormatter::FRACTION_DIGITS,
                            (int)config('app.currency.default_decimal_places')
                        );

                        if ($discount === null || $discount->price <= 0) {
                            return $number_formatter->formatCurrency(
                                $record->price,
                                $currency
                            );
                        }

                        $old_price = $number_formatter->formatCurrency(
                            $record->price,
                            $currency
                        );
                        $new_price = $number_formatter->formatCurrency(
                            $discount->price,
                            $currency
                        );

                        return '<span style="font-size: 1rem; text-decoration: line-through; color: #9ca3af;"><del>' . $old_price . '</del></span><br>' .
                            '<span style="font-size: 1.3rem; color: #ef4444; font-weight: 600;">' . $new_price . '</span>';
                    })
                    ->sortable(),

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
                    ->label(__('admin/default.columns.is_active'))
                    ->boolean(),

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
                    ->placeholder(__('admin/default.filters.placeholder_all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),

                Filter::make('name')
                    ->label(__('admin/default.filters.name'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/default.filters.name'))
                            ->placeholder(__('admin/default.filters.placeholder_name'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(trim($state)) < 3) {
                                    $set('name', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['name'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(trim($search)) < 3) {
                            return $query;
                        }

                        $search = trim($search);

                        return $query->whereHas(
                            'productDescription',
                            fn(Builder $query): Builder => $query->where('name', 'like', "%$search%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['name'] ?? null;

                        if ($search === null || Str::length($search) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.name') . ': ' . trim($search);
                    }),

                Filter::make('model')
                    ->label(__('admin/default.filters.model'))
                    ->schema([
                        TextInput::make('model')
                            ->label(__('admin/default.filters.model'))
                            ->placeholder(__('admin/default.filters.placeholder_model'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(trim($state)) < 3) {
                                    $set('model', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['model'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(trim($search)) < 3) {
                            return $query;
                        }

                        $search = trim($search);

                        return $query->whereLike('model', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['model'] ?? null;

                        if ($search === null || Str::length($search) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.model') . ': ' . trim($search);
                    }),

                Filter::make('sku')
                    ->label(__('admin/default.filters.sku'))
                    ->schema([
                        TextInput::make('sku')
                            ->label(__('admin/default.filters.sku'))
                            ->placeholder(__('admin/default.filters.placeholder_sku'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(trim($state)) < 3) {
                                    $set('sku', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['sku'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(trim($search)) < 3) {
                            return $query;
                        }

                        $search = trim($search);

                        return $query->whereLike('sku', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['sku'] ?? null;

                        if ($search === null || Str::length($search) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.sku') . ': ' . trim($search);
                    }),

                Filter::make('ean')
                    ->label(__('admin/default.filters.ean'))
                    ->schema([
                        TextInput::make('ean')
                            ->label(__('admin/default.filters.ean'))
                            ->placeholder(__('admin/default.filters.placeholder_ean'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(trim($state)) < 3) {
                                    $set('ean', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['ean'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(trim($search)) < 3) {
                            return $query;
                        }

                        $search = trim($search);

                        return $query->whereLike('ean', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['ean'] ?? null;

                        if ($search === null || Str::length($search) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.ean') . ': ' . trim($search);
                    }),

                Filter::make('price')
                    ->label(__('admin/default.filters.price'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin/default.filters.price'))
                            ->placeholder(__('admin/default.filters.placeholder_price'))
                            ->numeric()
                            ->minValue(0)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if (!is_numeric($state) || $state < 0) {
                                    $set('price', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['price'] ?? null;

                        // Apply validation in query
                        if (!is_numeric($search) || $search < 0) {
                            return $query;
                        }

                        return $query->where('price', $search);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['price'] ?? null;

                        if (!is_numeric($search) || $search < 0) {
                            return null;
                        }

                        return __('admin/default.filters.price') . ': ' . $search;
                    }),

                Filter::make('quantity')
                    ->label(__('admin/default.filters.quantity'))
                    ->schema([
                        TextInput::make('quantity')
                            ->label(__('admin/default.filters.quantity'))
                            ->placeholder(__('admin/default.filters.placeholder_quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if (!is_numeric($state) || $state < 0) {
                                    $set('quantity', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['quantity'] ?? null;

                        // Apply validation in query
                        if (!is_numeric($search) || $search < 0) {
                            return $query;
                        }

                        return $query->where('quantity', $search);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['quantity'] ?? null;

                        if (!is_numeric($search) || $search < 0) {
                            return null;
                        }

                        return __('admin/default.filters.quantity') . ': ' . $search;
                    }),

                Filter::make('category')
                    ->label(__('admin/default.filters.category'))
                    ->schema([
                        TextInput::make('category')
                            ->label(__('admin/default.filters.category'))
                            ->placeholder(__('admin/default.filters.placeholder_category'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(trim($state)) < 3) {
                                    $set('category', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['category'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(trim($search)) < 3) {
                            return $query;
                        }

                        $search = trim($search);

                        return $query->whereLike('category', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['category'] ?? null;

                        if ($search === null || Str::length($search) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.category') . ': ' . trim($search);
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
