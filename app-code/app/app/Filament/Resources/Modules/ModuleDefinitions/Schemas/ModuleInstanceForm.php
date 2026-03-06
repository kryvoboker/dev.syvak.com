<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class ModuleInstanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::getComponents());
    }

    /**
     * @return array<int, Component>
     */
    public static function getComponents(): array
    {
        return [
            TextInput::make('name')
                ->label(__('admin/modules/module_instances.labels.name'))
                ->maxLength(255)
                ->required(),

            TextInput::make('slug')
                ->label(__('admin/modules/module_instances.labels.slug'))
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
                ->default(true)
                ->required(),

            TextInput::make('sort_order')
                ->label(__('admin/modules/module_instances.labels.sort_order'))
                ->numeric()
                ->default(0)
                ->required(),

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
