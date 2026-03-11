<?php

declare(strict_types=1);

namespace App\Services\Modules;

use App\Models\Modules\ModuleDefinition;
use App\Models\Modules\ModuleInstance;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ModuleInstanceSettingsNormalizerService
{
    public function __construct(
        private readonly ModuleClassResolverService $module_class_resolver_service,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalizeForDefinition(ModuleDefinition $definition, array $attributes): array
    {
        $normalizer_class = $this->module_class_resolver_service->resolve($definition, 'Services\\ModuleSettingsNormalizerService');

        if ($normalizer_class === null) {
            return $attributes;
        }

        $settings = Arr::get($attributes, 'settings', []);

        Log::channel('daily')->info('Normalizing module instance settings for definition.', [
            'definition_id' => $definition->id,
            'nwidart_name'  => $definition->nwidart_name,
        ]);

        /** @var object{normalize: callable} $normalizer */
        $normalizer             = app($normalizer_class);
        $attributes['settings'] = $normalizer->normalize(is_array($settings) ? $settings : []);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalizeForInstance(ModuleInstance $instance, array $attributes): array
    {
        $definition = $instance->definition()->first();

        if ($definition === null) {
            return $attributes;
        }

        return $this->normalizeForDefinition($definition, $attributes);
    }
}
