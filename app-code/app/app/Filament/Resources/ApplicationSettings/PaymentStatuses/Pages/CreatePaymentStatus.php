<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\PaymentStatuses\Pages;

use App\Exceptions\PaymentStatusInvariantException;
use App\Filament\Resources\ApplicationSettings\PaymentStatuses\PaymentStatusResource;
use App\Services\Payment\PaymentStatusManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreatePaymentStatus extends CreateRecord
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

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PaymentStatusManagementService::class)->create($data);
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

    private function sendInvariantNotification(string $reason): void
    {
        Notification::make()
            ->title(__('admin/default.errors.title'))
            ->body(__('admin/settings/payment_statuses.errors.' . $reason))
            ->danger()
            ->send();
    }
}
