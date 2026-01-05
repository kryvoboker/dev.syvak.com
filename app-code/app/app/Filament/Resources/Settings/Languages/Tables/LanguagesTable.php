<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Tables;

use App\Filament\Resources\Trait\Tables\BooleanTableTrait;
use App\Filament\Resources\Trait\Tables\CommonTextTableTrait;
use App\Filament\Resources\Trait\Tables\DateTableTrait;
use App\Models\Settings\Language;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LanguagesTable
{
    use CommonTextTableTrait, BooleanTableTrait, DateTableTrait;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                self::getCodeTableField(),

                self::getNameTableField(),

                self::getIsActiveTableField(),

                self::getIsDefaultTableField(),

                self::getCreatedAtTableField([
                    'isToggledHiddenByDefault' => false,
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),

                TernaryFilter::make('is_default')
                    ->label(__('admin/default.filters.default'))
                    ->trueLabel(__('admin/default.filters.default_only')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records) {
                            // Check if trying to delete default language
                            $has_default = $records->contains('is_default', true);

                            if ($has_default) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/languages.errors.cant_delete_default_language'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active languages
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = Language::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/languages.errors.cant_delete_last_active_language'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
    }
}
