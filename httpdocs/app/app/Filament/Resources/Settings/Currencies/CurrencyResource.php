<?php

namespace App\Filament\Resources\Settings\Currencies;

use App\Filament\Resources\Settings\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Settings\Currencies\Pages\EditCurrency;
use App\Filament\Resources\Settings\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Settings\Currencies\Schemas\CurrencyForm;
use App\Filament\Resources\Settings\Currencies\Tables\CurrenciesTable;
use App\Models\Settings\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string                $model                = Currency::class;
    protected static string|null|UnitEnum   $navigationGroup      = 'Settings';
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::CurrencyDollar;
    protected static ?string                $recordTitleAttribute = 'currency';

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit'   => EditCurrency::route('/{record}/edit'),
        ];
    }
}
