<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\Orders\Orders;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OrderResource extends Resource
{
    use TotalModelItemsResourceTrait;

    protected static ?string $model = Orders::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Orders;

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/orders/orders.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/orders/orders.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/orders/orders.labels.plural_model');
    }
}
