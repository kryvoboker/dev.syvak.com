<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web     : __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health  : '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Override storage path immediately after app creation
$new_storage_path = $_ENV['NEW_STORAGE_PATH'] ?? $_SERVER['NEW_STORAGE_PATH'] ?? null;

if ($new_storage_path) {
    $app->useStoragePath($new_storage_path);
}

// Override public path
$new_public_path = $_ENV['NEW_PUBLIC_PATH'] ?? $_SERVER['NEW_PUBLIC_PATH'] ?? null;

if ($new_public_path) {
    $app->usePublicPath($new_public_path);
}

return $app;
