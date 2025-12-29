<?php

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInfoPage extends EditRecord
{
    protected static string $resource = InfoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
