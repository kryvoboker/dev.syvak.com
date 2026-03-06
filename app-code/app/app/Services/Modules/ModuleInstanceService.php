<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ModuleInstanceService
{
    public function __construct(
        private readonly ModuleCacheService $module_cache_service,
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
     */
    public function createFromDefinition(ModuleDefinition $definition, array $attributes = []): ModuleInstance
    {
        Log::channel('daily')->info('Creating module instance from definition.', [
            'definition_id' => $definition->id,
            'slug'          => $definition->slug,
        ]);

        try {
            /** @var ModuleInstance $instance */
            $instance = DB::transaction(function () use ($definition, $attributes): ModuleInstance {
                $next_sort_order = ((int) $definition->instances()->max('sort_order')) + 1;
                $default_name    = trim($definition->name . ' ' . $next_sort_order);
                $name            = (string) Arr::get($attributes, 'name', $default_name);
                $slug            = $this->generateUniqueSlug((string) Arr::get($attributes, 'slug', Str::slug($name)));

                return $definition->instances()->create([
                    'name'        => $name,
                    'slug'        => $slug,
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
     */
    public function duplicate(ModuleInstance $instance, array $attributes = []): ModuleInstance
    {
        Log::channel('daily')->info('Duplicating module instance.', [
            'instance_id'     => $instance->id,
            'definition_id'   => $instance->module_definition_id,
            'instance_slug'   => $instance->slug,
            'definition_slug' => $instance->definition?->slug,
        ]);

        try {
            /** @var ModuleInstance $duplicated_instance */
            $duplicated_instance = DB::transaction(function () use ($instance, $attributes): ModuleInstance {
                $definition      = $instance->definition()->firstOrFail();
                $next_sort_order = ((int) $definition->instances()->max('sort_order')) + 1;
                $duplicated_name = (string) Arr::get($attributes, 'name', $instance->name . ' Copy');
                $duplicated_slug = $this->generateUniqueSlug((string) Arr::get($attributes, 'slug', Str::slug($duplicated_name)));

                return $definition->instances()->create([
                    'name'        => $duplicated_name,
                    'slug'        => $duplicated_slug,
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
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(ModuleInstance $instance, array $settings): ModuleInstance
    {
        Log::channel('daily')->info('Updating module instance settings.', [
            'instance_id'    => $instance->id,
            'definition_id'  => $instance->module_definition_id,
            'settings_count' => count($settings),
        ]);

        $instance->forceFill([
            'settings' => $settings,
        ])->save();

        $this->module_cache_service->flush();

        return $instance->refresh();
    }

    private function generateUniqueSlug(string $base_slug): string
    {
        $prepared_slug = Str::slug($base_slug);
        $prepared_slug = filled($prepared_slug) ? $prepared_slug : Str::random(12);
        $candidate     = $prepared_slug;
        $counter       = 2;

        while (ModuleInstance::query()->where('slug', $candidate)->exists()) {
            $candidate = $prepared_slug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
