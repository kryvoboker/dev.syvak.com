<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->last_sync_summary !== null)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ __('admin/modules/module_definitions.sections.last_sync_summary') }}
                    </div>
                </div>

                <div class="grid gap-4 p-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('admin/modules/module_definitions.labels.created') }}
                        </div>
                        <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ data_get($this->last_sync_summary, 'created', 0) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('admin/modules/module_definitions.labels.updated') }}
                        </div>
                        <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ data_get($this->last_sync_summary, 'updated', 0) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('admin/modules/module_definitions.labels.missing') }}
                        </div>
                        <div class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ data_get($this->last_sync_summary, 'missing', 0) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_14rem]">
            <label class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('admin/modules/module_definitions.filters.search') }}
                </span>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('admin/modules/module_definitions.filters.search_placeholder') }}"
                    class="fi-input block w-full rounded-lg border-none bg-white/80 px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition dark:bg-white/5 dark:text-white dark:ring-white/10"
                />
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('admin/modules/module_definitions.filters.status') }}
                </span>

                <select
                    wire:model.live="status_filter"
                    class="fi-input block w-full rounded-lg border-none bg-white/80 px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition dark:bg-white/5 dark:text-white dark:ring-white/10"
                >
                    <option value="all">{{ __('admin/modules/module_definitions.filters.status_all') }}</option>
                    <option value="enabled">{{ __('admin/modules/module_definitions.filters.enabled_only') }}</option>
                    <option value="disabled">{{ __('admin/modules/module_definitions.filters.disabled_only') }}</option>
                </select>
            </label>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <table class="w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('admin/modules/module_definitions.columns.name') }}
                        </th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('admin/modules/module_definitions.columns.status') }}
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('admin/modules/module_definitions.columns.actions') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($this->getDefinitions() as $definition)
                        <tr class="bg-gray-50/80 dark:bg-white/3">
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-col gap-1">
                                    <div class="text-base font-semibold text-gray-950 dark:text-white">
                                        {{ $definition->name }}
                                    </div>

                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $definition->nwidart_name }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top text-sm text-gray-700 dark:text-gray-200">
                                {{ $definition->is_enabled
                                    ? __('admin/modules/module_definitions.status.enabled')
                                    : __('admin/modules/module_definitions.status.disabled') }}
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @php($definition_instance_action_url = $this->getDefinitionInstanceActionUrl($definition))

                                    @if (filled($definition_instance_action_url))
                                        <a
                                            href="{{ $definition_instance_action_url }}"
                                            class="fi-btn fi-btn-color-primary fi-btn-size-sm rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500"
                                        >
                                            {{ $this->getDefinitionInstanceActionLabel($definition) }}
                                        </a>
                                    @endif

                                    @php($module_action_url = $this->getModuleActionUrl($definition))

                                    @if (filled($module_action_url))
                                        <a
                                            href="{{ $module_action_url }}"
                                            class="fi-btn fi-btn-color-gray fi-btn-size-sm rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
                                        >
                                            {{ __('admin/modules/module_definitions.actions.open_module') }}
                                        </a>
                                    @endif

                                    @if ($definition->is_enabled)
                                        <button
                                            type="button"
                                            wire:click="disableDefinition({{ $definition->id }})"
                                            wire:confirm="{{ __('admin/modules/module_definitions.confirmations.disable_definition') }}"
                                            class="rounded-lg bg-danger-600 px-3 py-2 text-sm font-medium text-white hover:bg-danger-500"
                                        >
                                            {{ __('admin/modules/module_definitions.actions.disable') }}
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="enableDefinition({{ $definition->id }})"
                                            class="rounded-lg bg-success-600 px-3 py-2 text-sm font-medium text-white hover:bg-success-500"
                                        >
                                            {{ __('admin/modules/module_definitions.actions.enable') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if ($this->shouldRenderDefinitionInstancesEmptyState($definition))
                            @forelse ($definition->instances as $instance)
                                <tr>
                                    <td class="px-4 py-4 align-top">
                                        <div class="pl-6 text-sm text-gray-900 dark:text-white">
                                            {{ $instance->name }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 align-top text-sm text-gray-700 dark:text-gray-200">
                                        {{ $instance->is_enabled
                                            ? __('admin/modules/module_definitions.status.enabled')
                                            : __('admin/modules/module_definitions.status.disabled') }}
                                    </td>

                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ $this->getEditUrl($instance) }}"
                                                class="rounded-lg border border-primary-500 px-3 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10"
                                            >
                                                {{ __('admin/modules/module_definitions.actions.edit_instance') }}
                                            </a>

                                            @if ($instance->is_enabled)
                                                <button
                                                    type="button"
                                                    wire:click="disableInstance({{ $instance->id }})"
                                                    wire:confirm="{{ __('admin/modules/module_definitions.confirmations.disable_instance') }}"
                                                    class="rounded-lg bg-warning-600 px-3 py-2 text-sm font-medium text-white hover:bg-warning-500"
                                                >
                                                    {{ __('admin/modules/module_definitions.actions.disable_instance') }}
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="enableInstance({{ $instance->id }})"
                                                    class="rounded-lg bg-success-600 px-3 py-2 text-sm font-medium text-white hover:bg-success-500"
                                                >
                                                    {{ __('admin/modules/module_definitions.actions.enable_instance') }}
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="deleteInstance({{ $instance->id }})"
                                                wire:confirm="{{ __('admin/modules/module_definitions.confirmations.delete_instance') }}"
                                                class="rounded-lg bg-danger-600 px-3 py-2 text-sm font-medium text-white hover:bg-danger-500"
                                            >
                                                {{ __('admin/modules/module_definitions.actions.delete_instance') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4">
                                        <div class="pl-6 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('admin/modules/module_definitions.empty.definition_instances') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('admin/modules/module_definitions.empty.list') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
