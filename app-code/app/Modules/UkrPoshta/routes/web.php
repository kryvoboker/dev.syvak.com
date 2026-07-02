<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UkrPoshta\Http\Controllers\UkrPoshtaController;

Route::prefix('ukrposhta')
    ->name('ukrposhta.')
    ->group(function (): void {
        Route::get('state', [UkrPoshtaController::class, 'state'])->name('state');
        Route::get('regions', [UkrPoshtaController::class, 'regions'])->name('regions');
        Route::get('districts', [UkrPoshtaController::class, 'districts'])->name('districts');
        Route::get('cities', [UkrPoshtaController::class, 'cities'])->name('cities');
        Route::get('post-offices', [UkrPoshtaController::class, 'postOffices'])->name('post-offices');
        Route::post('selection', [UkrPoshtaController::class, 'saveSelection'])->name('selection.save');
    });
