<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\RelationManagers;

use App\Filament\Resources\Modules\ModuleDefinitions\Schemas\ModuleInstanceForm;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use App\Services\Modules\ModuleCacheService;
use App\Services\Modules\ModuleInstanceService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ModuleInstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'instances';

    public function form(Schema $schema): Schema
    {
        return ModuleInstanceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/modules/module_instances.columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('admin/modules/module_instances.columns.slug'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('placement')
                    ->label(__('admin/modules/module_instances.columns.placement'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('context_key')
                    ->label(__('admin/modules/module_instances.columns.context_key'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_enabled')
                    ->label(__('admin/modules/module_instances.columns.is_enabled'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('admin/modules/module_instances.columns.sort_order'))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('admin/modules/module_instances.filters.is_enabled'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/modules/module_instances.filters.enabled_only'))
                    ->falseLabel(__('admin/modules/module_instances.filters.disabled_only')),
            ])
            ->headerActions([
                Action::make('add')
                    ->label(__('admin/modules/module_instances.actions.add'))
                    ->icon(Heroicon::Plus)
                    ->schema(ModuleInstanceForm::getComponents())
                    ->action(function (array $data, ModuleInstanceService $module_instance_service): void {
                        /** @var ModuleDefinition $definition */
                        $definition = $this->getOwnerRecord();

                        $module_instance_service->createFromDefinition($definition, $data);

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_instances.notifications.created'))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('duplicate')
                    ->label(__('admin/modules/module_instances.actions.duplicate'))
                    ->icon(Heroicon::DocumentDuplicate)
                    ->color('primary')
                    ->schema(ModuleInstanceForm::getComponents())
                    ->fillForm(function (ModuleInstance $record): array {
                        return [
                            'name'        => $record->name . ' Copy',
                            'slug'        => $record->slug . '-copy',
                            'placement'   => $record->placement,
                            'context_key' => $record->context_key,
                            'is_enabled'  => $record->is_enabled,
                            'sort_order'  => $record->sort_order + 1,
                            'settings'    => $record->settings ?? [],
                            'meta'        => $record->meta ?? [],
                        ];
                    })
                    ->action(function (ModuleInstance $record, array $data, ModuleInstanceService $module_instance_service): void {
                        $module_instance_service->duplicate($record, $data);

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_instances.notifications.duplicated'))
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->schema(ModuleInstanceForm::getComponents())
                    ->after(function (ModuleInstance $record, ModuleInstanceService $module_instance_service): void {
                        $module_instance_service->updateSettings($record, $record->settings ?? []);
                    }),

                DeleteAction::make()
                    ->after(function (ModuleCacheService $module_cache_service): void {
                        $module_cache_service->flush();
                    }),
            ])
            ->defaultSort('sort_order');
    }
}
