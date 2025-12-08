<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Categories\Categories\Tables;

use App\Models\Catalogs\Categories\Category;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoriesTable
{
    /**
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load descriptions to avoid N+1 problem
                return $query->with('categoryDescription');
            })
            ->columns([
                TextColumn::make('categoryDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Category $record) {
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

                        // Try to get description for current locale
                        $description = $record->categoryDescription
                            ->firstWhere('language_id', $current_language_id);

                        // Fallback to first available description
                        if (!$description) {
                            $description = $record->categoryDescription->first();
                        }

                        return $description?->name ?? '-';
                    }),

                ImageColumn::make('image')
                    ->label(__('admin/default.columns.image'))
                    ->imageSize((int)config('app.images.category.preview_in_list_in_admin.width'))
                    ->checkFileExistence()
                    ->defaultImageUrl(Storage::url(config('app.images.category.no_image')))
                    ->extraImgAttributes([
                        'decoding' => 'async',
                        'loading'  => 'lazy',
                        'style'    => 'object-fit: contain;',
                    ]),

                TextColumn::make('sort_order')
                    ->label(__('admin/default.columns.sort_order'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin/default.columns.is_active'))
                    ->boolean(),

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
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear empty input to avoid filtering by empty value
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('name', null);
                                }
                            })
                    ])
                    ->query(function (Builder $query, array $data) {
                        $search = $data['name'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'categoryDescription',
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
