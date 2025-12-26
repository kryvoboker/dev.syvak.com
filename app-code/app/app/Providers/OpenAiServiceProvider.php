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
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $key = (string)config('open-ai.api_key');

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
