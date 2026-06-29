<?php

use Illuminate\Support\Facades\Route;
use Modules\NovaPoshta\Http\Controllers\NovaPoshtaController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('novaposhtas', NovaPoshtaController::class)->names('novaposhta');
});
