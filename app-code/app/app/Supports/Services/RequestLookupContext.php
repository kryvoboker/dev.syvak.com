<?php

declare(strict_types=1);

namespace App\Supports\Services;

use App\Models\ApplicationSettings\Language;
use App\Models\Modules\ModuleDefinition;
use Illuminate\Database\Eloquent\Collection;

/**
 * Holds immutable lookup data for the lifetime of one HTTP request.
 */
final class RequestLookupContext
{
    /** @var Collection<int, Language>|null */
    private ?Collection $active_languages = null;

    /** @var Collection<int, ModuleDefinition>|null */
    private ?Collection $enabled_module_definitions = null;

    /**
     * @return Collection<int, Language>
     */
    public function getActiveLanguages(): Collection
    {
        return $this->active_languages ??= Language::query()
            ->select(['id', 'code', 'name', 'is_active', 'is_default'])
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function getLanguageByCode(string $code): ?Language
    {
        return $this->getActiveLanguages()->firstWhere('code', $code);
    }

    public function forgetActiveLanguages(): void
    {
        $this->active_languages = null;
    }

    public function getDefaultLanguage(): ?Language
    {
        return $this->getActiveLanguages()->firstWhere('is_default', true)
            ?? $this->getActiveLanguages()->first();
    }

    /**
     * @return Collection<int, ModuleDefinition>
     */
    public function getEnabledModuleDefinitions(): Collection
    {
        return $this->enabled_module_definitions ??= ModuleDefinition::query()
            ->select([
                'id',
                'nwidart_name',
                'slug',
                'name',
                'is_enabled',
                'is_installed',
                'is_enabled_in_filesystem',
                'sort_order',
                'meta',
            ])
            ->enabled()
            ->ordered()
            ->get();
    }

    public function isEnabledSingletonModule(string $module_name): bool
    {
        $module_definition = $this->getEnabledModuleDefinitions()
            ->firstWhere('nwidart_name', $module_name);

        return $module_definition instanceof ModuleDefinition
            && $module_definition->canCreateInstances() === false;
    }

    public function forgetEnabledModuleDefinitions(): void
    {
        $this->enabled_module_definitions = null;
    }
}
