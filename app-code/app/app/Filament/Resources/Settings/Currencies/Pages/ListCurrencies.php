<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Pages;

use App\Filament\Resources\Settings\Currencies\CurrencyResource;
use App\Services\Currency\UpdateRatesService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/settings/currencies.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/currencies.navigation_label');
    }

    /**
     * @return array|Action[]|ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            // Exchange rate update button
            Action::make('updateCurrencyRates')
                ->label(__('admin/settings/currencies.actions.update_rates'))
                ->icon(Heroicon::ArrowPath)
                ->requiresConfirmation()
                ->modalHeading(__('admin/default.success.title'))
                ->modalDescription(__('admin/settings/currencies.actions.modal_update_rates_body'))
                ->action(function (): void {
                    $error_message = app(UpdateRatesService::class)->handle();

                    if ($error_message) {
                        Notification::make()
                            ->title($error_message)
                            ->danger()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/settings/currencies.notifications.rates_updated_body'))
                            ->success()
                            ->send();
                    }
                }),

            // Standard button for creating currency
            CreateAction::make(),
        ];
    }
}
