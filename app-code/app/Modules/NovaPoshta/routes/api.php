<?php

use Illuminate\Support\Facades\Route;
use Modules\NovaPoshta\Http\Controllers\NovaPoshtaController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('novaposhtas', NovaPoshtaController::class)->names('novaposhta');
});
