<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    if (
        config('debugbar.enabled') === false ||
        config('debugbar.storage.enabled') === false ||
        config('debugbar.storage.driver') !== 'file'
    ) {
        return;
    }

    $max_files     = 50;
    $debugbar_path = config('debugbar.storage.path');
    $files         = glob($debugbar_path . '/*.json');

    if (!$files || count($files) <= $max_files) {
        return;
    }

    // Sort by modification time (oldest first)
    usort($files, fn(string $a, string $b): int => filemtime($a) - filemtime($b));

    $files_to_delete = array_slice($files, 0, count($files) - $max_files);

    foreach ($files_to_delete as $file) {
        @unlink($file);
    }
})
    ->hourly()
    ->environments(['local']);
