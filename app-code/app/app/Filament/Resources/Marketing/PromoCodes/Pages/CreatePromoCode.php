<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Pages;

use App\Filament\Resources\Marketing\PromoCodes\PromoCodeResource;
use App\Models\Marketing\PromoCode;
use App\Services\Marketing\PromoCodePersistenceService;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    private array $relationship_data = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $prepared_data = app(PromoCodePersistenceService::class)->prepareForSave($data);
        $this->relationship_data = $prepared_data['relationships'];

        return $prepared_data['attributes'];
    }

    protected function afterCreate(): void
    {
        /** @var PromoCode $record */
        $record = $this->record;
        app(PromoCodePersistenceService::class)->syncRelations($record, $this->relationship_data);
    }
}
