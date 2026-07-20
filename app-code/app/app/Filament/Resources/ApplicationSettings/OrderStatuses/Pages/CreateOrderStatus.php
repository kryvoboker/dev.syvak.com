<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\OrderStatuses\Pages;

use App\Exceptions\OrderStatusInvariantException;
use App\Filament\Resources\ApplicationSettings\OrderStatuses\OrderStatusResource;
use App\Services\Order\OrderStatusManagementService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateOrderStatus extends CreateRecord
{
    protected static string $resource = OrderStatusResource::class;

    public function getTitle(): string
    {
        return __('admin/settings/order_statuses.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/settings/order_statuses.navigation_label');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(OrderStatusManagementService::class)->create($data);
        } catch (OrderStatusInvariantException $exception) {
            $this->sendInvariantNotification($exception->reason);
            $this->halt();
            throw $exception;
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/settings/order_statuses.errors.operation_failed'))
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
            ->body(__('admin/settings/order_statuses.errors.' . $reason))
            ->danger()
            ->send();
    }
}
