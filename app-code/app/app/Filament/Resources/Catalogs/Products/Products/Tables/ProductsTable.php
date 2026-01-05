<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Products\Products\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Filament\Resources\Trait\Tables\ImageTableTrait;
use App\Filament\Resources\Trait\Tables\SlugTableTrait;
use App\Models\Catalogs\Products\Product;
use App\Supports\Services\Currency\ConvertPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductsTable
{
    use LanguageTrait, CommonTextTableTrait, BooleanTableTrait, DateTableTrait, ImageTableTrait, SlugTableTrait;

    /**
     * @param Table $table
     *
     * @return Table
     */
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
                self::getNameTableField([
                    'filed_name'         => 'productDescription.name',
                    'searchable'         => ['name'],
                    'get_state_using_cb' => function (Product $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $description = $record->productDescription
                            ->firstWhere('language_id', $current_language_id);

                        if (!$description) {
                            $description = $record->productDescription->first();
                        }

                        return $description?->name ?? '-';
                    },
                ]),

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

                self::getImageTableField(),

                TextColumn::make('price')
                    ->label(__('admin/default.columns.price'))
                    ->html()
                    ->getStateUsing(function (Product $record) {
                        $discount = new Product()->getLastActualAndLastModifiedDiscountFromModel($record);

                        $currency      = config('app.currency.default_currency_code');
                        $exchange_rate = (float)config('app.currency.default_exchange_rate');

                        $convert_price = app(ConvertPrice::class);

                        if ($discount === null || $discount->price <= 0) {
                            return $convert_price->format($record->price, $currency, $exchange_rate);
                        }

                        $old_price = $convert_price->format($record->price, $currency, $exchange_rate);
                        $new_price = $convert_price->format($discount->price, $currency, $exchange_rate);

                        return '<span style="font-size: 1rem; text-decoration: line-through; color: #9ca3af;"><del>' . $old_price . '</del></span><br>' .
                            '<span style="font-size: 1.3rem; color: #ef4444; font-weight: 600;">' . $new_price . '</span>';
                    })
                    ->sortable(),

                TextColumn::make('viewed')
                    ->label(__('admin/default.columns.viewed'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                self::getDateAvailableTableField(),

                self::getDateAddedTableField(),

                self::getIsActiveTableField(),

                self::getSlugTableField([
                    'filed_name'         => 'slugs.slug',
                    'searchable'         => ['slug'],
                    'get_state_using_cb' => function (Product $product) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $product->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    },
                ]),

                self::getCreatedAtTableField(),

                self::getUpdatedAtTableField(),
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
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('name', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['name'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'productDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('name', 'LIKE', "%$search%");
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['name'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
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
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('model', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['model'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereLike('model', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['model'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.model') . ': ' . Str::trim($search);
                    }),

                Filter::make('sku')
                    ->label(__('admin/default.filters.sku'))
                    ->schema([
                        TextInput::make('sku')
                            ->label(__('admin/default.filters.sku'))
                            ->placeholder(__('admin/default.placeholders.sku'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('sku', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['sku'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereLike('sku', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['sku'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.sku') . ': ' . Str::trim($search);
                    }),

                Filter::make('ean')
                    ->label(__('admin/default.filters.ean'))
                    ->schema([
                        TextInput::make('ean')
                            ->label(__('admin/default.filters.ean'))
                            ->placeholder(__('admin/default.placeholders.ean'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('ean', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['ean'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereLike('ean', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['ean'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.ean') . ': ' . Str::trim($search);
                    }),

                Filter::make('price')
                    ->label(__('admin/default.filters.price'))
                    ->schema([
                        TextInput::make('price')
                            ->label(__('admin/default.filters.price'))
                            ->placeholder(__('admin/default.placeholders.price'))
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
                            ->placeholder(__('admin/default.placeholders.quantity'))
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
                            ->placeholder(__('admin/default.placeholders.category'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('category', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['category'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereLike('category', "$search%");
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['category'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.category') . ': ' . Str::trim($search);
                    }),

                Filter::make('slugs')
                    ->label(__('admin/default.filters.slug'))
                    ->schema([
                        TextInput::make('slugs')
                            ->label(__('admin/default.filters.slug'))
                            ->placeholder(__('admin/default.placeholders.slug'))
                            ->minLength(3)
                            ->maxLength(500)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear empty input to avoid filtering by empty value
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('slugs', null);
                                }
                            })
                    ])
                    ->query(function (Builder $query, array $data) {
                        $search = $data['slugs'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'slugs',
                            function (Builder $query) use ($search) {
                                return $query
                                    ->whereLike('slug', "$search%");
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['slugs'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.slug') . ': ' . Str::trim($search);
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
