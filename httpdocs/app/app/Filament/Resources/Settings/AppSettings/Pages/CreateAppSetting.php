<?php

namespace App\Filament\Resources\Settings\AppSettings\Pages;

use App\Filament\Resources\Settings\AppSettings\AppSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppSetting extends CreateRecord
{
    protected static string $resource = AppSettingResource::class;
}
