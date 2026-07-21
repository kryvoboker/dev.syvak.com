<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\OrderStatuses\Pages;

use App\Filament\Resources\ApplicationSettings\OrderStatuses\OrderStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderStatuses extends ListRecords
{
    protected static string $resource = OrderStatusResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/order_statuses.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/order_statuses.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
