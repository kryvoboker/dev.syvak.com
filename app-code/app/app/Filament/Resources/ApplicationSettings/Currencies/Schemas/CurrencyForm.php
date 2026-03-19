<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('admin/default.labels.code'))
                    ->helperText(__('admin/settings/currencies.helpers.code'))
                    ->maxLength(3)
                    ->placeholder('USD')
                    ->rules(['required', 'alpha', 'uppercase', 'size:3'])
                    ->unique(ignoreRecord: true)
                    ->required(),

                TextInput::make('name')
                    ->label(__('admin/default.labels.name'))
                    ->helperText(__('admin/settings/currencies.helpers.name'))
                    ->maxLength(100)
                    ->placeholder('US Dollar')
                    ->rules(['required', 'string', 'max:100'])
                    ->required(),

                TextInput::make('format_locale')
                    ->label(__('admin/default.labels.format_locale'))
                    ->helperText(__('admin/settings/currencies.helpers.format_locale'))
                    ->maxLength(10)
                    ->placeholder('uk_UA')
                    ->rules(['required', 'string', 'max:10'])
                    ->required(),

                TextInput::make('symbol_left')
                    ->label(__('admin/settings/currencies.labels.symbol_left'))
                    ->helperText(__('admin/settings/currencies.helpers.symbol_left'))
                    ->maxLength(10)
                    ->placeholder('$')
                    ->rules(['nullable', 'string', 'max:10'])
                    ->nullable(),

                TextInput::make('symbol_right')
                    ->label(__('admin/settings/currencies.labels.symbol_right'))
                    ->helperText(__('admin/settings/currencies.helpers.symbol_right'))
                    ->maxLength(10)
                    ->placeholder('€')
                    ->rules(['nullable', 'string', 'max:10'])
                    ->nullable(),

                TextInput::make('decimal_places')
                    ->label(__('admin/settings/currencies.labels.decimal_places'))
                    ->helperText(__('admin/settings/currencies.helpers.decimal_places'))
                    ->numeric()
                    ->rules(['required', 'numeric', 'min:0', 'max:4'])
                    ->minValue(0)
                    ->maxValue(4)
                    ->default(2)
                    ->required(),

                TextInput::make('exchange_rate')
                    ->label(__('admin/settings/currencies.labels.exchange_rate'))
                    ->helperText(__('admin/settings/currencies.helpers.exchange_rate'))
                    ->numeric()
                    ->rules(['required', 'numeric', 'min:0.000001'])
                    ->minValue(0.000001)
                    ->step(0.000001)
                    ->default(1.000000)
                    ->required(),

                Toggle::make('is_active')
                    ->label(__('admin/default.labels.is_active'))
                    ->helperText(__('admin/settings/currencies.helpers.is_active'))
                    ->default(false)
                    ->required(),

                Toggle::make('is_default')
                    ->label(__('admin/default.labels.is_default'))
                    ->helperText(__('admin/settings/currencies.helpers.is_default'))
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($state) {
                            $set('is_active', true);
                        }
                    })
                    ->required(),
            ]);
    }
}
