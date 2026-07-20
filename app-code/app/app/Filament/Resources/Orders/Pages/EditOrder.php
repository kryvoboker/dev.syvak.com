<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Orders\Orders;
use App\Services\Order\OrderAdminPersistenceService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('admin/orders/orders.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/orders/orders.navigation_label');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Orders) {
            return $data;
        }

        $data['id'] = $record->getKey();
        $data['customer'] = $record->customer?->toArray() ?? [];
        $data['shipping'] = $record->shipping?->toArray() ?? [];
        $data['payments'] = $record->payments->toArray();
        $data['products'] = $record->products->toArray();
        $data['totals'] = $record->totals->toArray();
        $data['histories'] = $record->histories->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Orders) {
            throw new \LogicException('Order record has invalid type.');
        }

        try {
            return app(OrderAdminPersistenceService::class)->update($record, $data);
        } catch (Throwable $throwable) {
            report($throwable);

            Notification::make()
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/orders/orders.errors.update_failed'))
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
                ->after(function (Orders $record): void {
                    self::logMutation('soft_delete', $record);
                }),
            RestoreAction::make()
                ->after(function (Orders $record): void {
                    self::logMutation('restore', $record);
                }),
            ForceDeleteAction::make()
                ->after(function (Orders $record): void {
                    self::logMutation('force_delete', $record);
                }),
        ];
    }

    private static function logMutation(string $action, Orders $record): void
    {
        Log::channel('daily')->info('[EditOrder] order mutation completed', [
            'action' => $action,
            'admin_user_id' => auth()->id(),
            'order_id' => $record->getKey(),
            'order_number' => $record->order_number,
        ]);
    }
}
