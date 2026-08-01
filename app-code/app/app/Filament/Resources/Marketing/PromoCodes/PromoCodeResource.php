<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Marketing\PromoCodes\Pages\CreatePromoCode;
use App\Filament\Resources\Marketing\PromoCodes\Pages\EditPromoCode;
use App\Filament\Resources\Marketing\PromoCodes\Pages\ListPromoCodes;
use App\Filament\Resources\Marketing\PromoCodes\Schemas\PromoCodeForm;
use App\Filament\Resources\Marketing\PromoCodes\Tables\PromoCodesTable;
use App\Models\Marketing\PromoCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Marketing;

    public static function form(Schema $schema): Schema
    {
        return PromoCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromoCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromoCodes::route('/'),
            'create' => CreatePromoCode::route('/create'),
            'edit' => EditPromoCode::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/marketing/promo_codes.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/marketing/promo_codes.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/marketing/promo_codes.labels.plural_model');
    }
}
