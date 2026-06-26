<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ModuleInstanceService
{
    public function __construct(
        private ModuleCacheService $module_cache_service,
        private ModuleInstanceSettingsNormalizerService $module_instance_settings_normalizer_service,
    ) {}

    public function setGlobalState(ModuleDefinition $definition, bool $is_enabled): ModuleDefinition
    {
        Log::channel('daily')->info('Changing module global state.', [
            'definition_id' => $definition->id,
            'slug'          => $definition->slug,
            'is_enabled'    => $is_enabled,
        ]);

        $definition->forceFill([
            'is_enabled' => $is_enabled,
        ])->save();

        $this->module_cache_service->flush();

        return $definition->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function createFromDefinition(ModuleDefinition $definition, array $attributes = []): ModuleInstance
    {
        Log::channel('daily')->info('Creating module instance from definition.', [
            'definition_id' => $definition->id,
            'nwidart_name'  => $definition->nwidart_name,
        ]);

        try {
            $attributes = $this->module_instance_settings_normalizer_service->normalizeForDefinition($definition, $attributes);

            /** @var ModuleInstance $instance */
            $instance = DB::transaction(function () use ($definition, $attributes): ModuleInstance {
                $next_sort_order = ((int) $definition->instances()->max('sort_order')) + 1;
                $default_name    = trim($definition->name . ' ' . $next_sort_order);
                $name            = (string) Arr::get($attributes, 'name', $default_name);

                return $definition->instances()->create([
                    'name'        => $name,
                    'placement'   => Arr::get($attributes, 'placement'),
                    'context_key' => Arr::get($attributes, 'context_key'),
                    'is_enabled'  => (bool) Arr::get($attributes, 'is_enabled', true),
                    'sort_order'  => (int) Arr::get($attributes, 'sort_order', $next_sort_order),
                    'settings'    => Arr::get($attributes, 'settings', []),
                    'meta'        => Arr::get($attributes, 'meta', []),
                ]);
            });

            $this->module_cache_service->flush();

            return $instance->refresh();
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Creating module instance failed.', [
                'definition_id' => $definition->id,
                'message'       => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws Throwable
     */
    public function duplicate(ModuleInstance $instance, array $attributes = []): ModuleInstance
    {
        Log::channel('daily')->info('Duplicating module instance.', [
            'instance_id'     => $instance->id,
            'definition_id'   => $instance->module_definition_id,
            'instance_name'   => $instance->name,
            'definition_name' => $instance->definition?->name,
        ]);

        try {
            $attributes = $this->module_instance_settings_normalizer_service->normalizeForInstance($instance, $attributes);

            /** @var ModuleInstance $duplicated_instance */
            $duplicated_instance = DB::transaction(function () use ($instance, $attributes): ModuleInstance {
                $definition      = $instance->definition()->firstOrFail();
                $next_sort_order = ((int) $definition->instances()->max('sort_order')) + 1;
                $duplicated_name = (string) Arr::get($attributes, 'name', $instance->name . ' Copy');

                return $definition->instances()->create([
                    'name'        => $duplicated_name,
                    'placement'   => Arr::get($attributes, 'placement', $instance->placement),
                    'context_key' => Arr::get($attributes, 'context_key', $instance->context_key),
                    'is_enabled'  => (bool) Arr::get($attributes, 'is_enabled', $instance->is_enabled),
                    'sort_order'  => (int) Arr::get($attributes, 'sort_order', $next_sort_order),
                    'settings'    => Arr::get($attributes, 'settings', $instance->settings ?? []),
                    'meta'        => Arr::get($attributes, 'meta', $instance->meta ?? []),
                ]);
            });

            $this->module_cache_service->flush();

            return $duplicated_instance->refresh();
        } catch (Throwable $throwable) {
            Log::channel('stack')->error('Duplicating module instance failed.', [
                'instance_id' => $instance->id,
                'message'     => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ModuleInstance $instance, array $attributes): ModuleInstance
    {
        Log::channel('daily')->info('Updating module instance.', [
            'instance_id'   => $instance->id,
            'definition_id' => $instance->module_definition_id,
        ]);

        $attributes = $this->module_instance_settings_normalizer_service->normalizeForInstance($instance, $attributes);

        $instance->forceFill([
            'name'        => Arr::get($attributes, 'name', $instance->name),
            'placement'   => Arr::get($attributes, 'placement', $instance->placement),
            'context_key' => Arr::get($attributes, 'context_key', $instance->context_key),
            'is_enabled'  => (bool) Arr::get($attributes, 'is_enabled', $instance->is_enabled),
            'sort_order'  => (int) Arr::get($attributes, 'sort_order', $instance->sort_order),
            'settings'    => Arr::get($attributes, 'settings', $instance->settings ?? []),
            'meta'        => Arr::get($attributes, 'meta', $instance->meta ?? []),
        ])->save();

        $this->module_cache_service->flush();

        return $instance->refresh();
    }

    public function setInstanceState(ModuleInstance $instance, bool $is_enabled): ModuleInstance
    {
        Log::channel('daily')->info('Changing module instance state.', [
            'instance_id'   => $instance->id,
            'definition_id' => $instance->module_definition_id,
            'is_enabled'    => $is_enabled,
        ]);

        $instance->forceFill([
            'is_enabled' => $is_enabled,
        ])->save();

        $this->module_cache_service->flush();

        return $instance->refresh();
    }

    public function delete(ModuleInstance $instance): void
    {
        Log::channel('daily')->info('Deleting module instance.', [
            'instance_id'   => $instance->id,
            'definition_id' => $instance->module_definition_id,
        ]);

        $instance->delete();

        $this->module_cache_service->flush();
    }
}
