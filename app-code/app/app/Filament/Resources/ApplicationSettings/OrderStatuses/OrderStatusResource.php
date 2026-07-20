<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\OrderStatuses;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\Pages\CreateOrderStatus;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\Pages\EditOrderStatus;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\Pages\ListOrderStatuses;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\Schemas\OrderStatusForm;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\Tables\OrderStatusesTable;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\Orders\OrderStatuses;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderStatusResource extends Resource
{
    use TotalModelItemsResourceTrait;

    protected static ?string $model = OrderStatuses::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::ApplicationSettings;

    public static function form(Schema $schema): Schema
    {
        return OrderStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderStatusesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderStatuses::route('/'),
            'create' => CreateOrderStatus::route('/create'),
            'edit' => EditOrderStatus::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/settings/order_statuses.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/settings/order_statuses.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/order_statuses.labels.plural_model');
    }
}
