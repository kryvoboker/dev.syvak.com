<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleInstances\Pages;

use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use App\Filament\Resources\Modules\ModuleInstances\ModuleInstanceResource;
use App\Filament\Resources\Modules\ModuleInstances\Schemas\ModuleInstanceForm;
use App\Models\Modules\ModuleInstance;
use App\Services\Modules\ModuleInstanceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Edit page for one saved module settings copy.
 *
 * It delegates form composition to the shared schema entry point and keeps only
 * page-specific concerns such as titles, redirects, and delete behavior.
 */
class EditModuleInstance extends EditRecord
{
    protected static string $resource = ModuleInstanceResource::class;

    public function getTitle(): string
    {
        /** @var ModuleInstance $instance */
        $instance = $this->getRecord();

        return __('admin/modules/module_instances.pages.edit_title', [
            'module' => $instance->name,
        ]);
    }

    public function getHeading(): ?string
    {
        return $this->getTitle();
    }

    public function form(Schema $schema): Schema
    {
        /** @var ModuleInstance $instance */
        $instance = $this->getRecord();

        return ModuleInstanceForm::configure($schema, $instance->definition, $instance);
    }

    protected function getRedirectUrl(): string
    {
        return ModuleInstanceResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
            DeleteAction::make()
                ->label(__('admin/modules/module_definitions.actions.delete_instance'))
                ->icon(Heroicon::Trash)
                ->action(function (ModuleInstance $record, ModuleInstanceService $module_instance_service): void {
                    $module_instance_service->delete($record);

                    Notification::make()
                        ->title(__('admin/default.success.title'))
                        ->body(__('admin/modules/module_definitions.notifications.instance_deleted'))
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(ModuleDefinitionResource::getUrl()),
        ];
    }
}
