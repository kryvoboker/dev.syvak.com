<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages\CreatePaymentStatus;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages\EditPaymentStatus;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages\ListPaymentStatuses;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\Schemas\PaymentStatusForm;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\Tables\PaymentStatusesTable;
use App\Filament\Resources\Trait\TotalModelItemsResourceTrait;
use App\Models\Payment\PaymentStatuses;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentStatusResource extends Resource
{
    use TotalModelItemsResourceTrait;

    protected static ?string $model = PaymentStatuses::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::ApplicationSettings;

    public static function form(Schema $schema): Schema
    {
        return PaymentStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentStatusesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentStatuses::route('/'),
            'create' => CreatePaymentStatus::route('/create'),
            'edit' => EditPaymentStatus::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/settings/payment_statuses.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/settings/payment_statuses.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/settings/payment_statuses.labels.plural_model');
    }
}
