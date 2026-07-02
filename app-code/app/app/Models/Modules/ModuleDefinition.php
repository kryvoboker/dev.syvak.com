<?php

declare(strict_types=1);

namespace App\Models\Modules;

use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleDefinition extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'nwidart_name',
        'module_path',
        'description',
        'is_installed',
        'is_enabled',
        'is_enabled_in_filesystem',
        'sort_order',
        'settings_schema',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'is_enabled' => 'boolean',
            'is_enabled_in_filesystem' => 'boolean',
            'sort_order' => 'integer',
            'settings_schema' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<ModuleInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(ModuleInstance::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query
            ->where('is_enabled', true)
            ->where('is_installed', true)
            ->where('is_enabled_in_filesystem', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return Collection<int, ModuleInstance>
     */
    public function getEnabledInstances(): Collection
    {
        /** @var Collection<int, ModuleInstance> $instances */
        $instances = $this->instances()->where('is_enabled', true)->get();

        return $instances;
    }

    public function canCreateInstances(): bool
    {
        return (bool) data_get($this->getAdminModuleConfig(), 'can_create_instances', true);
    }

    public function getAdminModuleListActionUrl(): ?string
    {
        $page_class = (string) data_get($this->getAdminModuleConfig(), 'module_list_action.page', '');

        if (blank($page_class) || ! class_exists($page_class) || ! is_subclass_of($page_class, Page::class)) {
            return null;
        }

        /** @var class-string<Page> $page_class */
        try {
            return $page_class::getUrl();
        } catch (Throwable $throwable) {
            Log::channel('stack')->warning('[FIX] Failed to resolve module admin page URL.', [
                'module_definition_id' => $this->id,
                'module_name' => $this->nwidart_name,
                'page_class' => $page_class,
                'error' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getAdminModuleConfig(): array
    {
        $admin_config = data_get($this->meta, 'admin', []);

        if (is_array($admin_config) && $admin_config !== []) {
            return $admin_config;
        }

        $module_config_path = base_path(
            'Modules' . DIRECTORY_SEPARATOR . $this->nwidart_name . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
        );

        if (! is_file($module_config_path)) {
            return [];
        }

        $module_config = require $module_config_path;

        if (! is_array($module_config)) {
            return [];
        }

        $admin_config = data_get($module_config, 'admin', []);

        return is_array($admin_config) ? $admin_config : [];
    }
}
