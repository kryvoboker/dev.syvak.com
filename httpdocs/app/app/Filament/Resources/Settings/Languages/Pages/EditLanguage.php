<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settings\Languages\Pages;

use App\Filament\Resources\Settings\Languages\LanguageResource;
use App\Models\Settings\Language;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return __('admin/settings/language.navigation_label');
    }

    /**
     * Get page heading
     *
     * @return string|null
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/language.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Language $record) {
                    if ($record->is_default) {
                        Notification::make()
                            ->title(__('admin/settings/language.text_cant_delete_default_language'))
                            ->body(__('admin/settings/language.error_cant_delete_default_language'))
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
