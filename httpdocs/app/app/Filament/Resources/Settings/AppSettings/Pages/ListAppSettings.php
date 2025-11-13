<?php

namespace App\Filament\Resources\Settings\AppSettings\Pages;

use App\Filament\Resources\Settings\AppSettings\AppSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppSettings extends ListRecords
{
    protected static string $resource = AppSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
