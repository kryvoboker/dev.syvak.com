<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Pages;

use App\Filament\Resources\Marketing\PromoCodes\PromoCodeResource;
use App\Models\Marketing\PromoCode;
use App\Services\Marketing\PromoCodePersistenceService;
use Filament\Resources\Pages\EditRecord;

class EditPromoCode extends EditRecord
{
    protected static string $resource = PromoCodeResource::class;

    private array $relationship_data = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PromoCode $record */
        $record = $this->record;

        return array_merge($data, app(PromoCodePersistenceService::class)->hydrateFormData($record));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PromoCode $record */
        $record = $this->record;
        $prepared_data = app(PromoCodePersistenceService::class)->prepareForSave($data, $record);
        $this->relationship_data = $prepared_data['relationships'];

        return $prepared_data['attributes'];
    }

    protected function afterSave(): void
    {
        /** @var PromoCode $record */
        $record = $this->record;
        app(PromoCodePersistenceService::class)->syncRelations($record, $this->relationship_data);
    }
}
