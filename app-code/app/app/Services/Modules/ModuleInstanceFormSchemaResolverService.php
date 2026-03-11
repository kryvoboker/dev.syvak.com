<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Filament\Schemas\Components\Component;

class ModuleInstanceFormSchemaResolverService
{
    public function __construct(
        private readonly ModuleClassResolverService $module_class_resolver_service,
    ) {}

    /**
     * @return array<int, Component>|null
     */
    public function resolve(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): ?array
    {
        $schema_class = $this->module_class_resolver_service->resolve($definition, 'Filament\\ModuleInstanceFormSchema');

        if ($schema_class === null) {
            return null;
        }

        /** @var object{getComponents: callable} $schema */
        $schema = app($schema_class);

        return $schema->getComponents($definition, $instance);
    }
}
