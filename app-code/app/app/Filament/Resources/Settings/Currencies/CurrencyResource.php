<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Settings\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Settings\Currencies\Pages\EditCurrency;
use App\Filament\Resources\Settings\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Settings\Currencies\Schemas\CurrencyForm;
use App\Filament\Resources\Settings\Currencies\Tables\CurrenciesTable;
use App\Filament\Resources\Trait\TotalItemsTrait;
use App\Models\Settings\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    use TotalItemsTrait;

    protected static ?string                $model                = Currency::class;
    protected static string|BackedEnum|null $navigationIcon       = Heroicon::CurrencyDollar;
    protected static ?string                $recordTitleAttribute = 'name';
    protected static string|null|UnitEnum   $navigationGroup      = AdminNavigationGroupEnum::Settings;

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
     * Signature in the navigation menu (left panel)
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('admin/settings/currencies.navigation_label');
    }

    /**
     * A single model name (e.g. in headings, "Create X" button)
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('admin/settings/currencies.labels.model');
    }

    /**
     * Plural model name (e.g. in lists, section headings)
     *
     * @return string
     */
    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/currencies.labels.plural_model');
    }
}
