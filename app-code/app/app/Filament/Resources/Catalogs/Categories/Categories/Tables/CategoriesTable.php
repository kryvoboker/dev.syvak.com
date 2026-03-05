<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Categories\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoriesTable
{
    use LanguageTrait;

    public static function configure(Table $table): Table
    {
        $current_language_id = self::getCurrentLanguageId();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load descriptions to avoid N+1 problem
                return $query->with([
                    'categoryDescription',
                    'categoryImage',
                    'slugs',
                ]);
            })
            ->columns([
                TextColumn::make('categoryDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Category $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        // Try to get description for current locale
                        $description = $record->categoryDescription
                            ->firstWhere('language_id', $current_language_id);

                        // Fallback to first available description
                        if (! $description) {
                            $description = $record->categoryDescription->first();
                        }

                        return $description?->name ?? '-';
                    }),

                ImageColumn::make('icon')
                    ->label(__('admin/default.columns.image'))
                    ->imageSize((int) config('app.images.category.preview_in_list_in_admin.width'))
                    ->checkFileExistence()
                    ->defaultImageUrl(config('app.images.category.no_image'))
                    ->extraImgAttributes([
                        'decoding' => 'async',
                        'loading'  => 'lazy',
                        'style'    => 'object-fit: contain; background-color: #f9f9f9;',
                    ])
                    ->getStateUsing(function (Category $category): ?string {
                        $category_image = $category->categoryImage()->first();

                        return $category_image?->icon
                            ? Storage::url($category_image->icon)
                            : null;
                    }),

                TextColumn::make('sort_order')
                    ->label(__('admin/default.columns.sort_order'))
                    ->numeric()
                    ->sortable(),

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
                    ->getStateUsing(function (Category $category) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $category->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    }),

                TextColumn::make('created_at')
                    ->label(__('admin/default.columns.created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('name')
                    ->label(__('admin/default.filters.name'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/default.filters.name'))
                            ->placeholder(__('admin/default.placeholders.name'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set): void {
                                if (str_more_or_equal_length($state, 3) === false) {
                                    $set('name', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['name'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'categoryDescription',
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

                Filter::make('slugs')
                    ->label(__('admin/default.filters.slug'))
                    ->schema([
                        TextInput::make('slugs')
                            ->label(__('admin/default.filters.slug'))
                            ->placeholder(__('admin/default.placeholders.slug'))
                            ->minLength(3)
                            ->maxLength(500)
                            ->afterStateUpdated(function ($state, $set): void {
                                if (str_more_or_equal_length($state, 3) === false) {
                                    $set('slugs', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['slugs'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas('slugs', fn (Builder $query): Builder => $query->whereLike('slug', "$search%"));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['slugs'] ?? null;

                        if (str_more_or_equal_length($search, 3) === false) {
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
