<?php

namespace App\Filament\Resources\Settings\AppSettings\Pages;

use App\Filament\Resources\Settings\AppSettings\AppSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppSetting extends EditRecord
{
    protected static string $resource = AppSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
