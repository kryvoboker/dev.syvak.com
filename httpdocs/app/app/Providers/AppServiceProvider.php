<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $new_storage_path = config('filesystems.new_storage_path');

        // Override the framework storage path
        app()->useStoragePath($new_storage_path);

        // Override compiled views path
        config(['view.compiled' => $new_storage_path . '/framework/views']);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
