<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Pages;

use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use Filament\Resources\Pages\EditRecord;

class EditModuleDefinition extends EditRecord
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
        return [];
    }
}
