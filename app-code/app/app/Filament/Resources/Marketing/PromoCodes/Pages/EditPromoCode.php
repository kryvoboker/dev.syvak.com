<?php

declare(strict_types=1);

namespace App\Filament\Resources\Marketing\PromoCodes\Pages;

use App\Filament\Resources\Marketing\PromoCodes\PromoCodeResource;
use App\Models\Marketing\PromoCode;
use App\Services\Marketing\PromoCodePersistenceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPromoCode extends EditRecord
{
    protected static string $resource = PromoCodeResource::class;

    /** @var array<string, mixed> */
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

    /**
     * @return array|Action[]|ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash),
        ];
    }
}
