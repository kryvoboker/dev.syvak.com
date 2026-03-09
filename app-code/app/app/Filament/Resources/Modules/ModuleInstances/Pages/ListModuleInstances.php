<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleInstances\Pages;

use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use App\Filament\Resources\Modules\ModuleInstances\ModuleInstanceResource;
use Filament\Resources\Pages\Page;
use Illuminate\Http\RedirectResponse;

/**
 * Redirect stub kept only because the hidden resource still needs an index page.
 *
 * The user-facing modules list lives on ModuleDefinitionResource, so every
 * request here is forwarded back to that grouped screen.
 */
class ListModuleInstances extends Page
{
    protected static string $resource = ModuleInstanceResource::class;

    public function mount(): RedirectResponse
    {
        return redirect()->to(ModuleDefinitionResource::getUrl());
    }
}
