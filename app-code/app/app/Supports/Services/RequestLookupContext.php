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
    private ?Language   $language_by_code           = null;
    private ?Language   $default_language           = null;

    /**
     * @return Collection<int, Language>
     */
    public function getActiveLanguages(): Collection
    {
        return $this->active_languages ??= app(Language::class)
            ->getActiveLanguages();
    }

    /**
     * @param string $code
     *
     * @return Language|null
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return $this->language_by_code ??= app(Language::class)
            ->getLanguageByCode($code);
    }

    /**
     * @return void
     */
    public function forgetLanguageByCode(): void
    {
        $this->language_by_code = null;
    }

    /**
     * @return void
     */
    public function forgetActiveLanguages(): void
    {
        $this->active_languages = null;
    }

    /**
     * @return Language|null
     */
    public function getDefaultLanguage(): ?Language
    {
        return $this->default_language ??= app(Language::class)
            ->getDefaultLanguage();
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

    /**
     * @param string $module_name
     *
     * @return bool
     */
    public function isEnabledSingletonModule(string $module_name): bool
    {
        $module_definition = $this->getEnabledModuleDefinitions()
            ->firstWhere('nwidart_name', $module_name);

        return $module_definition instanceof ModuleDefinition
            && $module_definition->canCreateInstances() === false;
    }

    /**
     * @return void
     */
    public function forgetEnabledModuleDefinitions(): void
    {
        $this->enabled_module_definitions = null;
    }
}
