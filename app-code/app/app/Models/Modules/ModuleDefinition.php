<?php

declare(strict_types=1);

namespace App\Models\Modules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'is_installed'             => 'boolean',
            'is_enabled'               => 'boolean',
            'is_enabled_in_filesystem' => 'boolean',
            'sort_order'               => 'integer',
            'settings_schema'          => 'array',
            'meta'                     => 'array',
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
}
