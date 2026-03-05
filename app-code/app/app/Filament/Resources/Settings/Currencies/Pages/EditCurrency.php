<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Currencies\Pages;

use App\Filament\Resources\Settings\Currencies\CurrencyResource;
use App\Models\Settings\Currency;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCurrency extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Currency $record) {
                    if ($record->is_default) {
                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/settings/currencies.errors.cant_delete_default_currency'))
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
