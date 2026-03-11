<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use Illuminate\Support\Str;

class ModuleClassResolverService
{
    public function resolve(ModuleDefinition|string|null $module_definition, string $relative_class): ?string
    {
        $nwidart_name = $this->extractModuleName($module_definition);

        if (blank($nwidart_name)) {
            return null;
        }

        $relative_class = Str::of($relative_class)
            ->trim('\\')
            ->toString();

        $class_name = sprintf('Modules\\%s\\%s', $nwidart_name, $relative_class);

        return class_exists($class_name) ? $class_name : null;
    }

    private function extractModuleName(ModuleDefinition|string|null $module_definition): ?string
    {
        if ($module_definition instanceof ModuleDefinition) {
            return filled($module_definition->nwidart_name) ? $module_definition->nwidart_name : null;
        }

        if (is_string($module_definition) && filled($module_definition)) {
            return $module_definition;
        }

        return null;
    }
}
