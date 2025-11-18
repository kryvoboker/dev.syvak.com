<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PathOverrideServiceProvider extends ServiceProvider
{
    /**
     * Register application paths override.
     */
    public function register(): void
    {
        /*$new_storage_path = $_ENV['NEW_STORAGE_PATH']
            ?? $_SERVER['NEW_STORAGE_PATH']
            ?? getenv('NEW_STORAGE_PATH')
            ?: null;

        $new_public_path = $_ENV['NEW_PUBLIC_PATH']
            ?? $_SERVER['NEW_PUBLIC_PATH']
            ?? getenv('NEW_PUBLIC_PATH')
            ?: null;*/

        $new_storage_path = config('filesystems.new_storage_path');
        $new_public_path  = config('filesystems.new_public_path');

        /*\Illuminate\Support\Facades\Log::channel('stack')->info('Storage path override', [
            'old' => storage_path(),
            'new' => $new_storage_path,
        ]);*/

        if ($new_storage_path && is_dir($new_storage_path)) {
            $this->app->useStoragePath($new_storage_path);
        }

        if ($new_public_path && is_dir($new_public_path)) {
            $this->app->usePublicPath($new_public_path);
        }



    }
}
