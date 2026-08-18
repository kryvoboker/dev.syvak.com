<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI;
use OpenAI\Client;
use RuntimeException;

class OpenAiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $configured_key = config('open-ai.api_key');
            $key = is_scalar($configured_key) ? (string) $configured_key : '';

            if ($key === '') {
                throw new RuntimeException('OpenAi API key is empty!');
            }

            return OpenAI::client($key);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
