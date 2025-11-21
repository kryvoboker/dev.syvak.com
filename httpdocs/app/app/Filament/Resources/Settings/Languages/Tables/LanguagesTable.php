<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Tables;

use App\Models\Settings\Language;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin/settings/languages.column_code'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label(__('admin/settings/languages.column_name'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin/settings/languages.column_active'))
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label(__('admin/settings/languages.column_default'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('admin/settings/languages.column_created_at'))
                    ->dateTime()
                    ->sortable()
                    ->date(config('app.datetime_format'), config('app.timezone')),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/settings/languages.filter_active'))
                    ->placeholder(__('admin/settings/languages.placeholder_all_languages'))
                    ->trueLabel(__('admin/settings/languages.true_label_active_only'))
                    ->falseLabel(__('admin/settings/languages.false_label_inactive_only')),

                TernaryFilter::make('is_default')
                    ->label(__('admin/settings/languages.filter_default'))
                    ->placeholder(__('admin/settings/languages.placeholder_all_languages'))
                    ->trueLabel(__('admin/settings/languages.true_label_default_only')),
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
                                    ->title(__('admin/settings/languages.text_cant_delete_default_language'))
                                    ->body(__('admin/settings/languages.error_cant_delete_default_language'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active languages
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = Language::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/settings/languages.text_cant_delete_last_active_language'))
                                    ->body(__('admin/settings/languages.error_cant_delete_last_active_language'))
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
