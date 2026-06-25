<?php

declare(strict_types=1);

namespace App\Services\Modules;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registers module providers safely and idempotently for current request.
 */
class ModuleProviderRegistrarService
{
    /**
     * @param  list<class-string>  $provider_classes
     *
     * @throws Throwable
     */
    public function register(array $provider_classes): void
    {
        foreach ($provider_classes as $provider_class) {
            if (app()->providerIsLoaded($provider_class)) {
                continue;
            }

            try {
                app()->register($provider_class);
            } catch (Throwable $throwable) {
                Log::channel('stack')->error('Failed to register module provider.', [
                    'provider_class' => $provider_class,
                    'message'        => $throwable->getMessage(),
                ]);

                throw $throwable;
            }
        }
    }
}
