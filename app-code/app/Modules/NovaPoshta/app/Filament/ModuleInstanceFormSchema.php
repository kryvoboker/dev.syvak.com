<?php

declare(strict_types=1);

namespace Modules\NovaPoshta\Filament;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\NovaPoshta\Support\NovaPoshtaConfig;

readonly class ModuleInstanceFormSchema
{
    public function __construct(
        private NovaPoshtaConfig $nova_poshta_config,
    ) {
    }

    /**
     * @return array<int, Component>
     */
    public function getComponents(?ModuleDefinition $definition = null, ?ModuleInstance $instance = null): array
    {
        unset($definition, $instance);

        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->columnSpanFull()
                        ->label(__('admin/modules/module_instances.labels.name'))
                        ->maxLength(255)
                        ->required(),

                    Grid::make()
                        ->columns()
                        ->columnSpanFull()
                        ->schema([
                            Select::make('placement')
                                ->label(__('admin/modules/module_instances.labels.placement'))
                                ->options(config('app.modules_placements', []))
                                ->required()
                                ->native(false),

                            TextInput::make('sort_order')
                                ->label(__('admin/modules/module_instances.labels.sort_order'))
                                ->numeric()
                                ->default(1),
                        ]),

                    Toggle::make('is_enabled')
                        ->columnSpanFull()
                        ->label(__('admin/modules/module_instances.labels.is_enabled'))
                        ->default(true),

                    Hidden::make('context_key')
                        ->default(null),

                    Section::make(__('admin/modules/nova_poshta.sections.shared'))
                        ->columnSpanFull()
                        ->schema([
                            Select::make('settings.shared.page_types')
                                ->label(__('admin/modules/nova_poshta.labels.page_types'))
                                ->multiple()
                                ->options($this->getPageTypeOptions())
                                ->required()
                                ->native(false)
                                ->default($this->nova_poshta_config->get('settings.default_page_types', ['checkout'])),

                            TextInput::make('settings.shared.api_key')
                                ->label(__('admin/modules/nova_poshta.labels.api_key'))
                                ->password()
                                ->revealable()
                                ->default((string) $this->nova_poshta_config->get('api.key', ''))
                                ->required()
                                ->helperText(__('admin/modules/nova_poshta.helpers.api_key')),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getPageTypeOptions(): array
    {
        return (array) config('page-settings.page_type', []);
    }
}
