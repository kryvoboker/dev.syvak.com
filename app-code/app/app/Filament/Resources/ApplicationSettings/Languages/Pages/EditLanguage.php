<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApplicationSettings\Languages\Pages;

use App\Filament\Resources\ApplicationSettings\Languages\LanguageResource;
use App\Models\ApplicationSettings\Language;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/settings/languages.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/settings/languages.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Language $record) {
                    if ($record->is_default) {
                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/settings/languages.errors.cant_delete_default_language'))
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
