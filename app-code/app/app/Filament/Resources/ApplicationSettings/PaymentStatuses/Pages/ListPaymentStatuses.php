<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages;

use App\Filament\Resources\ApplicationSettings\PaymentStatuses\PaymentStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentStatuses extends ListRecords
{
    protected static string $resource = PaymentStatusResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/payment_statuses.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/payment_statuses.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
