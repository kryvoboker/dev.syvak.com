<?php

declare(strict_types=1);

namespace App\Filament\Resources\Infos\InfoPages\Pages;

use App\Filament\Resources\Infos\InfoPages\InfoPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInfoPages extends ListRecords
{
    protected static string $resource = InfoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
