<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Schemas;

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
                    ->label(__('admin/settings/currency.label_code'))
                    ->helperText(__('admin/settings/currency.helper_code'))
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true)
                    ->placeholder('USD')
                    ->rules(['alpha', 'uppercase', 'size:3']),

                TextInput::make('name')
                    ->label(__('admin/settings/currency.label_name'))
                    ->helperText(__('admin/settings/currency.helper_name'))
                    ->required()
                    ->maxLength(100)
                    ->placeholder('US Dollar'),

                TextInput::make('symbol_left')
                    ->label(__('admin/settings/currency.label_symbol_left'))
                    ->helperText(__('admin/settings/currency.helper_symbol_left'))
                    ->maxLength(10)
                    ->placeholder('$'),

                TextInput::make('symbol_right')
                    ->label(__('admin/settings/currency.label_symbol_right'))
                    ->helperText(__('admin/settings/currency.helper_symbol_right'))
                    ->maxLength(10)
                    ->placeholder('€'),

                TextInput::make('decimal_places')
                    ->label(__('admin/settings/currency.label_decimal_places'))
                    ->helperText(__('admin/settings/currency.helper_decimal_places'))
                    ->required()
                    ->numeric()
                    ->default(2)
                    ->minValue(0)
                    ->maxValue(4)
                    ->placeholder('2'),

                TextInput::make('exchange_rate')
                    ->label(__('admin/settings/currency.label_exchange_rate'))
                    ->helperText(__('admin/settings/currency.helper_exchange_rate'))
                    ->required()
                    ->numeric()
                    ->default(1.000000)
                    ->minValue(0.000001)
                    ->step(0.000001)
                    ->placeholder('1.000000'),

                Toggle::make('is_active')
                    ->label(__('admin/settings/currency.label_is_active'))
                    ->helperText(__('admin/settings/currency.helper_is_active'))
                    ->default(false),

                Toggle::make('is_default')
                    ->label(__('admin/settings/currency.label_is_default'))
                    ->helperText(__('admin/settings/currency.helper_is_default'))
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Ensure only one default currency
                        if ($state) {
                            $set('is_active', true);
                        }
                    }),
            ]);
    }
}
