<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleInstances\Schemas;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use App\Services\Modules\ModuleInstanceFormSchemaResolverService;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

/**
 * Single Filament entry point for module settings schemas.
 *
 * Module-specific schemas, such as Carousel, are resolved through the service.
 * The generic fallback remains here only for modules that do not define their
 * own editor schema yet.
 */
class ModuleInstanceForm
{
    public static function configure(Schema $schema, ?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): Schema
    {
        return $schema
            ->components(static::getComponents($definition, $instance));
    }

    /**
     * @return array<int, Component>
     */
    public static function getComponents(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): array
    {
        $custom_components = app(ModuleInstanceFormSchemaResolverService::class)
            ->resolve($definition, $instance);

        if ($custom_components !== null) {
            return $custom_components;
        }

        return [
            TextInput::make('name')
                ->label(__('admin/modules/module_instances.labels.name'))
                ->maxLength(255)
                ->required(),

            TextInput::make('placement')
                ->label(__('admin/modules/module_instances.labels.placement'))
                ->maxLength(255)
                ->placeholder(__('admin/modules/module_instances.placeholders.placement')),

            TextInput::make('context_key')
                ->label(__('admin/modules/module_instances.labels.context_key'))
                ->maxLength(255)
                ->placeholder(__('admin/modules/module_instances.placeholders.context_key')),

            Toggle::make('is_enabled')
                ->label(__('admin/modules/module_instances.labels.is_enabled'))
                ->default(true),

            TextInput::make('sort_order')
                ->label(__('admin/modules/module_instances.labels.sort_order'))
                ->numeric()
                ->default(0),

            KeyValue::make('settings')
                ->label(__('admin/modules/module_instances.labels.settings'))
                ->keyLabel(__('admin/modules/module_instances.labels.setting_key'))
                ->valueLabel(__('admin/modules/module_instances.labels.setting_value'))
                ->reorderable(),

            KeyValue::make('meta')
                ->label(__('admin/modules/module_instances.labels.meta'))
                ->keyLabel(__('admin/modules/module_instances.labels.setting_key'))
                ->valueLabel(__('admin/modules/module_instances.labels.setting_value'))
                ->reorderable(),
        ];
    }
}
