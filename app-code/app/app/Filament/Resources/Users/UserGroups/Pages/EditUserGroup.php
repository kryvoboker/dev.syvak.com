<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\UserGroups\Pages;

use App\Filament\Resources\Users\UserGroups\UserGroupResource;
use App\Models\Users\UserGroup;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUserGroup extends EditRecord
{
    protected static string $resource = UserGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->before(function (DeleteAction $action, UserGroup $record) {
                    if ($record->is_default) {
                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/users/user_groups.errors.cant_delete_default_user_group'))
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return __('admin/users/user_groups.navigation_label');
    }

    /**
     * Get page heading
     */
    public function getHeading(): ?string
    {
        return __('admin/users/user_groups.navigation_label');
    }
}
