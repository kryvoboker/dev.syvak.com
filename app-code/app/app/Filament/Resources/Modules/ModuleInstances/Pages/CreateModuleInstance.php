<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleInstances\Pages;

use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use App\Filament\Resources\Modules\ModuleInstances\ModuleInstanceResource;
use App\Filament\Resources\Modules\ModuleInstances\Schemas\ModuleInstanceForm;
use App\Models\Modules\ModuleDefinition;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;

/**
 * Create page for one module settings copy scoped to a selected definition.
 *
 * The page keeps the resolved definition stable across Livewire updates so the
 * module-specific Filament schema can render without falling back incorrectly.
 */
class CreateModuleInstance extends CreateRecord
{
    protected static string $resource = ModuleInstanceResource::class;

    #[Locked]
    public ?int $definition_id = null;

    public function mount(): void
    {
        $definition = $this->getDefinition();
        $this->definition_id = $definition?->id;

        abort_if($this->definition_id === null, 404);
        abort_if($definition instanceof ModuleDefinition && ! $definition->canCreateInstances(), 404);

        parent::mount();
    }

    public function getTitle(): string
    {
        $definition = $this->getDefinition();

        return __('admin/modules/module_instances.pages.create_title', [
            'module' => $definition instanceof ModuleDefinition
                ? $definition->name
                : __('admin/modules/module_definitions.navigation_label'),
        ]);
    }

    public function getHeading(): ?string
    {
        return $this->getTitle();
    }

    public function form(Schema $schema): Schema
    {
        return ModuleInstanceForm::configure($schema, $this->getDefinition());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $definition = $this->getDefinition();

        abort_if($definition === null, 404);
        abort_if(! $definition->canCreateInstances(), 404);

        $data['module_definition_id'] = $definition->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ModuleInstanceResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->url(ModuleDefinitionResource::getUrl());
    }

    private function getDefinition(): ?ModuleDefinition
    {
        if ($this->definition_id !== null) {
            return ModuleDefinition::query()->find($this->definition_id);
        }

        $definition = ModuleInstanceResource::resolveDefinitionFromRequest(request());

        return $definition;
    }
}
