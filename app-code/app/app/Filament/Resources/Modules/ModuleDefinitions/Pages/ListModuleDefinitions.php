<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\ModuleDefinitions\Pages;

use App\Filament\Pages\Wiki\ModulesWikiPage;
use App\Filament\Resources\Modules\ModuleDefinitions\ModuleDefinitionResource;
use App\Filament\Resources\Modules\ModuleInstances\ModuleInstanceResource;
use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use App\Services\Modules\ModuleInstanceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Custom modules list page with grouped definition and settings rows.
 *
 * The page owns filtering, grouped data preparation, and row actions so the
 * resource layer stays thin and predictable.
 */
class ListModuleDefinitions extends ListRecords
{
    protected static string $resource = ModuleDefinitionResource::class;

    protected string $view = 'filament.pages.modules.module-definitions.pages.list-module-definitions';

    public string $search = '';

    public string $status_filter = 'all';

    public function getTitle(): string
    {
        return __('admin/modules/module_definitions.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/modules/module_definitions.navigation_label');
    }

    public function getDefinitions(): \Illuminate\Support\Collection
    {
        $search_value  = Str::lower(Str::squish($this->search));
        $status_filter = $this->status_filter;

        /** @var Collection<int, ModuleDefinition> $definitions */
        $definitions = ModuleDefinition::query()
            ->with(['instances' => fn ($query) => $query->ordered()])
            ->ordered()
            ->get();

        return $definitions
            ->map(function (ModuleDefinition $definition) use ($search_value, $status_filter): ?ModuleDefinition {
                $filtered_instances = $definition->instances
                    ->filter(function (ModuleInstance $instance) use ($search_value, $status_filter): bool {
                        if ($status_filter === 'enabled' && ! $instance->is_enabled) {
                            return false;
                        }

                        if ($status_filter === 'disabled' && $instance->is_enabled) {
                            return false;
                        }

                        if (blank($search_value)) {
                            return true;
                        }

                        return Str::contains(Str::lower($instance->name), $search_value);
                    })
                    ->values();

                $definition_matches = blank($search_value)
                    || Str::contains(Str::lower($definition->name), $search_value)
                    || Str::contains(Str::lower($definition->nwidart_name), $search_value);

                if ($status_filter === 'enabled' && ! $definition->is_enabled) {
                    $definition_matches = false;
                }

                if ($status_filter === 'disabled' && $definition->is_enabled) {
                    $definition_matches = false;
                }

                if ($definition_matches && blank($search_value)) {
                    $definition->setRelation('instances', $filtered_instances);

                    return $definition;
                }

                if ($definition_matches || $filtered_instances->isNotEmpty()) {
                    $definition->setRelation('instances', $filtered_instances);

                    return $definition;
                }

                return null;
            })
            ->filter(fn (?ModuleDefinition $definition): bool => $definition instanceof ModuleDefinition)
            ->values();
    }

    public function getCreateUrl(ModuleDefinition $definition): string
    {
        return ModuleInstanceResource::getUrl('create', ['definition' => $definition->id]);
    }

    public function getEditUrl(ModuleInstance $instance): string
    {
        return ModuleInstanceResource::getUrl('edit', ['record' => $instance]);
    }

    public function enableDefinition(int $definition_id): void
    {
        $definition = ModuleDefinition::query()->findOrFail($definition_id);

        app(ModuleInstanceService::class)->setGlobalState($definition, true);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/module_definitions.notifications.enabled'))
            ->success()
            ->send();
    }

    public function disableDefinition(int $definition_id): void
    {
        $definition = ModuleDefinition::query()->findOrFail($definition_id);

        app(ModuleInstanceService::class)->setGlobalState($definition, false);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/module_definitions.notifications.disabled'))
            ->success()
            ->send();
    }

    public function enableInstance(int $instance_id): void
    {
        $instance = ModuleInstance::query()->findOrFail($instance_id);

        app(ModuleInstanceService::class)->setInstanceState($instance, true);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/module_definitions.notifications.instance_enabled'))
            ->success()
            ->send();
    }

    public function disableInstance(int $instance_id): void
    {
        $instance = ModuleInstance::query()->findOrFail($instance_id);

        app(ModuleInstanceService::class)->setInstanceState($instance, false);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/module_definitions.notifications.instance_disabled'))
            ->success()
            ->send();
    }

    public function deleteInstance(int $instance_id): void
    {
        $instance = ModuleInstance::query()->findOrFail($instance_id);

        app(ModuleInstanceService::class)->delete($instance);

        Notification::make()
            ->title(__('admin/default.success.title'))
            ->body(__('admin/modules/module_definitions.notifications.instance_deleted'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncModuleDefinitions')
                ->label(__('admin/modules/module_definitions.actions.sync_modules'))
                ->icon(Heroicon::ArrowPath)
                ->action(function (): void {
                    try {
                        Notification::make()
                            ->title(__('admin/default.success.title'))
                            ->body(__('admin/modules/module_definitions.notifications.sync_completed'))
                            ->success()
                            ->send();
                    } catch (Throwable $throwable) {
                        report($throwable);

                        Notification::make()
                            ->title(__('admin/default.errors.title'))
                            ->body(__('admin/modules/module_definitions.notifications.sync_failed'))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('open_wiki')
                ->label(__('admin/wiki.actions.open_wiki'))
                ->icon(Heroicon::BookOpen)
                ->url(fn (): string => ModulesWikiPage::getUrl(), shouldOpenInNewTab: true),
        ];
    }
}
