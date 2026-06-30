<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\NovaPoshta\Http\Controllers\NovaPoshtaController;

Route::prefix('novaposhta')
    ->name('novaposhta.')
    ->group(function (): void {
        Route::get('state', [NovaPoshtaController::class, 'state'])->name('state');
        Route::get('regions', [NovaPoshtaController::class, 'regions'])->name('regions');
        Route::get('cities', [NovaPoshtaController::class, 'cities'])->name('cities');
        Route::get('post-offices', [NovaPoshtaController::class, 'postOffices'])->name('post-offices');
        Route::get('poshtomats', [NovaPoshtaController::class, 'poshtomats'])->name('poshtomats');
        Route::post('selection', [NovaPoshtaController::class, 'saveSelection'])->name('selection.save');
    });
