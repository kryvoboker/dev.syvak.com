<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages;

use App\Exceptions\PaymentStatusInvariantException;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\PaymentStatusResource;
use App\Models\Payment\PaymentStatuses;
use App\Services\Payment\PaymentStatusManagementService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Throwable;

class EditPaymentStatus extends EditRecord
{
    protected static string $resource = PaymentStatusResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/payment_statuses.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/payment_statuses.navigation_label');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof PaymentStatuses) {
            $data['descriptions'] = $record->descriptions
                ->keyBy('language_id')
                ->map(fn ($description): array => [
                    'name' => $description->name,
                ])
                ->toArray();
        }

        return $data;
    }

    /**
     * @param Model $record
     * @param array $data
     *
     * @throws Halt
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PaymentStatuses) {
            throw new LogicException('Payment status record has invalid type.');
        }

        try {
            return app(PaymentStatusManagementService::class)->update($record, $data);
        } catch (PaymentStatusInvariantException $exception) {
            $this->sendInvariantNotification($exception->reason);
            $this->halt();
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/settings/payment_statuses.errors.operation_failed'))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->before(function (DeleteAction $action, PaymentStatuses $record): void {
                    if ($record->is_default) {
                        $this->sendInvariantNotification('cannot_delete_default');
                        $action->cancel();
                    }
                }),
        ];
    }

    private function sendInvariantNotification(string $reason): void
    {
        Notification::make()
            ->title(__('admin/default.errors.title'))
            ->body(__('admin/settings/payment_statuses.errors.' . $reason))
            ->danger()
            ->send();
    }
}
