<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ModuleDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make(__('admin/modules/module_definitions.sections.definition'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('admin/modules/module_definitions.labels.name'))
                                    ->disabled(),

                                TextInput::make('slug')
                                    ->label(__('admin/modules/module_definitions.labels.slug'))
                                    ->disabled(),

                                TextInput::make('nwidart_name')
                                    ->label(__('admin/modules/module_definitions.labels.nwidart_name'))
                                    ->disabled(),

                                TextInput::make('module_path')
                                    ->label(__('admin/modules/module_definitions.labels.module_path'))
                                    ->disabled(),

                                Textarea::make('description')
                                    ->label(__('admin/modules/module_definitions.labels.description'))
                                    ->rows(4),
                            ])
                            ->columnSpan(1),

                        Section::make(__('admin/modules/module_definitions.sections.state'))
                            ->schema([
                                Toggle::make('is_installed')
                                    ->label(__('admin/modules/module_definitions.labels.is_installed'))
                                    ->disabled(),

                                Toggle::make('is_enabled')
                                    ->label(__('admin/modules/module_definitions.labels.is_enabled'))
                                    ->disabled(),

                                Toggle::make('is_enabled_in_filesystem')
                                    ->label(__('admin/modules/module_definitions.labels.is_enabled_in_filesystem'))
                                    ->disabled(),

                                TextInput::make('sort_order')
                                    ->label(__('admin/modules/module_definitions.labels.sort_order'))
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columnSpan(1),
                    ]),

                Section::make(__('admin/modules/module_definitions.sections.settings_schema'))
                    ->schema([
                        KeyValue::make('settings_schema')
                            ->label(__('admin/modules/module_definitions.labels.settings_schema'))
                            ->keyLabel(__('admin/modules/module_instances.labels.setting_key'))
                            ->valueLabel(__('admin/modules/module_instances.labels.setting_value'))
                            ->reorderable(),
                    ]),

                Section::make(__('admin/modules/module_definitions.sections.meta'))
                    ->schema([
                        Textarea::make('meta')
                            ->label(__('admin/modules/module_definitions.labels.meta'))
                            ->rows(12)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]'),
                    ]),
            ]);
    }
}
