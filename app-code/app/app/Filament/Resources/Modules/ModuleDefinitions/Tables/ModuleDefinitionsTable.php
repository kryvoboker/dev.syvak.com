<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Tables;

use App\Filament\Resources\Modules\ModuleDefinitions\Schemas\ModuleInstanceForm;
use App\Models\Modules\ModuleDefinition;
use App\Services\Modules\ModuleDefinitionSyncService;
use App\Services\Modules\ModuleInstanceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ModuleDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/modules/module_definitions.columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nwidart_name')
                    ->label(__('admin/modules/module_definitions.columns.nwidart_name'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('slug')
                    ->label(__('admin/modules/module_definitions.columns.slug'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('instances_count')
                    ->label(__('admin/modules/module_definitions.columns.instances_count'))
                    ->counts('instances')
                    ->sortable(),

                IconColumn::make('is_enabled')
                    ->label(__('admin/modules/module_definitions.columns.is_enabled'))
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_installed')
                    ->label(__('admin/modules/module_definitions.columns.is_installed'))
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_enabled_in_filesystem')
                    ->label(__('admin/modules/module_definitions.columns.is_enabled_in_filesystem'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('module_path')
                    ->label(__('admin/modules/module_definitions.columns.module_path'))
                    ->limit(40)
                    ->tooltip(fn (ModuleDefinition $record): ?string => $record->module_path)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('admin/default.columns.updated_at'))
                    ->date(config('app.datetime_format'), config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('admin/modules/module_definitions.filters.is_enabled'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/modules/module_definitions.filters.enabled_only'))
                    ->falseLabel(__('admin/modules/module_definitions.filters.disabled_only')),

                TernaryFilter::make('is_installed')
                    ->label(__('admin/modules/module_definitions.filters.is_installed'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/modules/module_definitions.filters.installed_only'))
                    ->falseLabel(__('admin/modules/module_definitions.filters.not_installed_only')),

                TernaryFilter::make('is_enabled_in_filesystem')
                    ->label(__('admin/modules/module_definitions.filters.is_enabled_in_filesystem'))
                    ->placeholder(__('admin/default.placeholders.all'))
                    ->trueLabel(__('admin/modules/module_definitions.filters.filesystem_enabled_only'))
                    ->falseLabel(__('admin/modules/module_definitions.filters.filesystem_disabled_only')),

                SelectFilter::make('slug')
                    ->label(__('admin/modules/module_definitions.filters.name'))
                    ->options(fn (): array => ModuleDefinition::query()->ordered()->pluck('name', 'slug')->all()),
            ])
            ->recordActions([
                Action::make('enable')
                    ->label(__('admin/modules/module_definitions.actions.enable'))
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ModuleDefinition $record): bool => ! $record->is_enabled)
                    ->action(function (ModuleDefinition $record, ModuleInstanceService $module_instance_service): void {
                        $module_instance_service->setGlobalState($record, true);

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_definitions.notifications.enabled'))
                            ->success()
                            ->send();
                    }),

                Action::make('disable')
                    ->label(__('admin/modules/module_definitions.actions.disable'))
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ModuleDefinition $record): bool => $record->is_enabled)
                    ->action(function (ModuleDefinition $record, ModuleInstanceService $module_instance_service): void {
                        $module_instance_service->setGlobalState($record, false);

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_definitions.notifications.disabled'))
                            ->success()
                            ->send();
                    }),

                Action::make('addInstance')
                    ->label(__('admin/modules/module_definitions.actions.add_instance'))
                    ->icon(Heroicon::Plus)
                    ->color('primary')
                    ->schema(ModuleInstanceForm::getComponents())
                    ->action(function (ModuleDefinition $record, array $data, ModuleInstanceService $module_instance_service): void {
                        $module_instance_service->createFromDefinition($record, $data);

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_instances.notifications.created'))
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('sync')
                    ->label(__('admin/modules/module_definitions.actions.sync'))
                    ->icon(Heroicon::ArrowPath)
                    ->requiresConfirmation()
                    ->action(function (ModuleDefinitionSyncService $module_definition_sync_service): void {
                        $summary = $module_definition_sync_service->sync();

                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_definitions.notifications.synced', $summary))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('sort_order');
    }
}
