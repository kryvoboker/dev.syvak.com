<?php

declare(strict_types=1);

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
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::CurrencyDollar;
    protected static ?string                $recordTitleAttribute = 'name';

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

    /**
     * Get the navigation label for the resource.
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/settings/currency.navigation_label');
    }

    /**
     * Get the model label for the resource.
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/settings/currency.label_model');
    }

    /**
     * Get the plural model label for the resource.
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/currency.label_plural_model');
    }

    /**
     * Get the navigation group for the resource.
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin/default.menu_item_settings');
    }
}
