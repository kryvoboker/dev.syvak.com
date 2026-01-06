<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Schemas;

use App\Filament\Resources\Trait\Forms\CommonTextFormTrait;
use App\Filament\Resources\Trait\Forms\ToggleCheckboxFormTrait;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyForm
{
    use CommonTextFormTrait, ToggleCheckboxFormTrait;

    /**
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('admin/default.labels.code'))
                    ->helperText(__('admin/settings/currencies.helpers.code'))
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true)
                    ->placeholder('USD')
                    ->rules(['alpha', 'uppercase', 'size:3']),

                self::getTextFormField([
                    'field_name'  => 'name',
                    'label'       => __('admin/default.labels.name'),
                    'helper_text' => __('admin/settings/currencies.helpers.name'),
                    'max_length'  => 100,
                    'placeholder' => 'US Dollar',
                ]),

                TextInput::make('format_locale')
                    ->label(__('admin/default.labels.format_locale'))
                    ->helperText(__('admin/settings/currencies.helpers.format_locale'))
                    ->required()
                    ->maxLength(10)
                    ->placeholder('uk_UA'),

                TextInput::make('symbol_left')
                    ->label(__('admin/settings/currencies.labels.symbol_left'))
                    ->helperText(__('admin/settings/currencies.helpers.symbol_left'))
                    ->maxLength(10)
                    ->placeholder('$'),

                TextInput::make('symbol_right')
                    ->label(__('admin/settings/currencies.labels.symbol_right'))
                    ->helperText(__('admin/settings/currencies.helpers.symbol_right'))
                    ->maxLength(10)
                    ->placeholder('€'),

                TextInput::make('decimal_places')
                    ->label(__('admin/settings/currencies.labels.decimal_places'))
                    ->helperText(__('admin/settings/currencies.helpers.decimal_places'))
                    ->required()
                    ->numeric()
                    ->default(2)
                    ->minValue(0)
                    ->maxValue(4)
                    ->placeholder('2'),

                TextInput::make('exchange_rate')
                    ->label(__('admin/settings/currencies.labels.exchange_rate'))
                    ->helperText(__('admin/settings/currencies.helpers.exchange_rate'))
                    ->required()
                    ->numeric()
                    ->default(1.000000)
                    ->minValue(0.000001)
                    ->step(0.000001)
                    ->placeholder('1.000000'),

                self::getIsActiveFormField([
                    'helper_text' => __('admin/settings/currencies.helpers.is_active'),
                    'default'     => false,
                ]),

                self::getIsDefaultFormField([
                    'helper_text' => __('admin/settings/currencies.helpers.is_default'),
                    'default'     => false,
                ]),
            ]);
    }
}
