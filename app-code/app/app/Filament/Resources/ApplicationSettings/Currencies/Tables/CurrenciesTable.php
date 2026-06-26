<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Currencies\Tables;

use App\Models\ApplicationSettings\Currency;
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
                    ->label(__('admin/default.columns.code'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label(__('admin/default.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('format_locale')
                    ->label(__('admin/default.columns.format_locale'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('symbol_left')
                    ->label(__('admin/settings/currencies.columns.symbol_left'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('symbol_right')
                    ->label(__('admin/settings/currencies.columns.symbol_right'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('decimal_places')
                    ->label(__('admin/settings/currencies.columns.decimal_places'))
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('exchange_rate')
                    ->label(__('admin/settings/currencies.columns.exchange_rate'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                IconColumn::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('admin/default.columns.created_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/default.filters.active'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/default.filters.active_only'))
                    ->falseLabel(__('admin/default.filters.inactive_only')),
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
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/currencies.errors.cant_delete_default_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }

                            // Check if trying to delete all active currencies
                            $active_to_delete = $records->where('is_active', true)->count();
                            $total_active = Currency::where('is_active', true)->count();

                            if ($active_to_delete >= $total_active) {
                                Notification::make()
                                    ->title(__('admin/default.errors.title'))
                                    ->body(__('admin/settings/currencies.errors.cant_delete_last_active_currency'))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
