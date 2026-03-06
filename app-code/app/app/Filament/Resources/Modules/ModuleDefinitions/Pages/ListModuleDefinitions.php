<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Pages;

use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use App\Services\Modules\ModuleDefinitionSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListModuleDefinitions extends ListRecords
{
    protected static string $resource = ModuleDefinitionResource::class;

    public function getTitle(): string
    {
        return __('admin/modules/module_definitions.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/modules/module_definitions.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label(__('admin/modules/module_definitions.actions.sync'))
                ->icon(Heroicon::ArrowPath)
                ->requiresConfirmation()
                ->action(function (ModuleDefinitionSyncService $module_definition_sync_service): void {
                    $summary = $module_definition_sync_service->sync();

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(__('admin/modules/module_definitions.notifications.synced', $summary))
                        ->success()
                        ->send();
                }),
        ];
    }
}
