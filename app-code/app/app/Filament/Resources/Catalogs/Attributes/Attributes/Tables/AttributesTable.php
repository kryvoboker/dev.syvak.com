<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Tables;

use App\Models\Catalogs\Attributes\Attribute;
use App\Models\Settings\Language;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttributesTable
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
                return $query->with('attributeDescription');
            })
            ->columns([
                TextColumn::make('attributeDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Attribute $record) {
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
                        $description = $record->attributeDescription
                            ->firstWhere('language_id', $current_language_id);

                        // Fallback to first available description
                        if (!$description) {
                            $description = $record->attributeDescription->first();
                        }

                        return $description?->name ?? '-';
                    }),

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
                //
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
