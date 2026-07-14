<?php

declare(strict_types=1);

namespace Modules\PaymentUponDelivery\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class PaymentUponDeliverySettingsPage extends Page
{
    protected static ?string $slug = 'modules/payment-upon-delivery';

    // phpcs:ignore
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Modules;

    public function getTitle(): string
    {
        return __('paymentupondelivery::admin/modules/payment_upon_delivery.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): string
    {
        return __('paymentupondelivery::admin/modules/payment_upon_delivery.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('paymentupondelivery::admin/modules/payment_upon_delivery.navigation_label');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
