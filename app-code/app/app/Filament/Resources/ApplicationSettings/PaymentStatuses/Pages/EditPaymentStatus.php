<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages;

use App\Exceptions\PaymentStatusInvariantException;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\PaymentStatusResource;
use App\Models\Payment\PaymentStatuses;
use App\Services\Payment\PaymentStatusManagementService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PaymentStatuses) {
            throw new \LogicException('Payment status record has invalid type.');
        }

        try {
            return app(PaymentStatusManagementService::class)->update($record, $data);
        } catch (PaymentStatusInvariantException $exception) {
            $this->sendInvariantNotification($exception->reason);
            $this->halt();
            throw $exception;
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/settings/payment_statuses.errors.operation_failed'))
                ->danger()
                ->send();

            $this->halt();
            throw $throwable;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
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
