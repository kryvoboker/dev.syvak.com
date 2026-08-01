<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Pages;

use App\Filament\Resources\Marketing\PromoCodes\PromoCodeResource;
use App\Models\Marketing\PromoCode;
use App\Services\Marketing\PromoCodePersistenceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

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

    /**
     * @return array|Action[]|ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.create'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->create()),
        ];
    }
}
