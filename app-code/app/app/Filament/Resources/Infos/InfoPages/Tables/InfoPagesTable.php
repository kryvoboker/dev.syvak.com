<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Infos\InfoPage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class InfoPagesTable
{
    use LanguageTrait;

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
                    'infoPageDescription',
                    'slugs',
                ]);
            })
            ->columns([
                TextColumn::make('infoPageDescription.title')
                    ->label(__('admin/default.columns.title'))
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function (InfoPage $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
                        }

                        $info_page_description = $record->infoPageDescription()
                            ->firstWhere('language_id', $current_language_id);

                        if ($info_page_description === null) {
                            $info_page_description = $record->infoPageDescription()->first();
                        }

                        return $info_page_description?->title ?? '-';
                    }),


                TextColumn::make('position')
                    ->label(__('admin/infos/info_pages.columns.position'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->label(__('admin/default.columns.sort_order'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin/default.columns.is_active'))
                    ->sortable()
                    ->boolean(),

                TextColumn::make('slugs')
                    ->label(__('admin/default.columns.slug'))
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (InfoPage $info_page) use ($current_language_id) {
                        if ($current_language_id === null) {
                            return '-';
                        }

                        $slug = $info_page->slugs
                            ->firstWhere('language_id', $current_language_id)?->slug;

                        return $slug ?? '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_noindex')
                    ->label(__('admin/default.columns.is_noindex'))
                    ->sortable()
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
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),

                Filter::make('title')
                    ->label(__('admin/default.filters.title'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('admin/default.filters.title'))
                            ->placeholder(__('admin/default.placeholders.title'))
                            ->minLength(3)
                            ->maxLength(255)
                            ->afterStateUpdated(function ($state, $set) {
                                // Clear invalid input
                                if ($state === null || Str::length(Str::trim($state)) < 3) {
                                    $set('title', null);
                                }
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = $data['title'] ?? null;

                        // Apply validation in query
                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return $query;
                        }

                        $search = Str::trim($search);

                        return $query->whereHas(
                            'infoPageDescription',
                            function (Builder $query) use ($search) {
                                return $query->where('title', 'LIKE', "%$search%");
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $search = $data['title'] ?? null;

                        if ($search === null || Str::length(Str::trim($search)) < 3) {
                            return null;
                        }

                        return __('admin/default.filters.title') . ': ' . Str::trim($search);
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
