<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogs\Attributes\Attributes\Tables;

use App\Filament\Resources\Trait\LanguageTrait;
use App\Models\Catalogs\Attributes\Attribute;
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
                return $query->with('attributeDescription');
            })
            ->columns([
                TextColumn::make('attributeDescription.name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable(['name'])
                    ->sortable()
                    ->limit(50)
                    ->getStateUsing(function (Attribute $record) use ($current_language_id) {
                        if (($returned_value = self::validateLanguageIdIsNotNull($current_language_id)) !== null) {
                            return $returned_value;
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
