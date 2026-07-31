<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Pages;

use App\Filament\Resources\Marketing\PromoCodes\PromoCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPromoCodes extends ListRecords
{
    protected static string $resource = PromoCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
