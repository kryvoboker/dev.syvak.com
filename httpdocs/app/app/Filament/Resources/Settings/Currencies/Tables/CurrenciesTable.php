<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Tables;

use App\Models\Settings\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin/settings/currencies.column_code'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label(__('admin/settings/currencies.column_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('symbol_left')
                    ->label(__('admin/settings/currencies.column_symbol_left'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('symbol_right')
                    ->label(__('admin/settings/currencies.column_symbol_right'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('decimal_places')
                    ->label(__('admin/settings/currencies.column_decimal_places'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('exchange_rate')
                    ->label(__('admin/settings/currencies.column_exchange_rate'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin/settings/currencies.column_active'))
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label(__('admin/settings/currencies.column_default'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('admin/settings/currencies.column_created_at'))
                    ->dateTime()
                    ->sortable()
                    ->date(config('app.datetime_format'), config('app.timezone')),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/settings/currencies.filter_active'))
                    ->placeholder(__('admin/settings/currencies.placeholder_all_currencies'))
                    ->trueLabel(__('admin/settings/currencies.true_label_active_only'))
                    ->falseLabel(__('admin/settings/currencies.false_label_inactive_only')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, Collection $records) {
                            // Check if trying to delete default currency
                            $has_default = $records->contains('is_default', true);

                            if ($has_default) {
                                Notification::make()
                                    ->title(__('admin/settings/currencies.text_cant_delete_default_currency'))
                                    ->body(__('admin/settings/currencies.error_cant_delete_default_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active currencies
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active     = Currency::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/settings/currencies.text_cant_delete_last_active_currency'))
                                    ->body(__('admin/settings/currencies.error_cant_delete_last_active_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
