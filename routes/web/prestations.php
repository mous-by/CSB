<?php

use App\Http\Controllers\Web\Admin\PrestationController;
use Illuminate\Support\Facades\Route;

// Services (phase 1). Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/prestations', [PrestationController::class, 'index'])->middleware('permission:prestations.view')->name('prestations.index');
    Route::post('/prestations', [PrestationController::class, 'store'])->middleware('permission:prestations.create')->name('prestations.store');
    Route::put('/prestations/{prestation}', [PrestationController::class, 'update'])->middleware('permission:prestations.update')->name('prestations.update');
    Route::delete('/prestations/{prestation}', [PrestationController::class, 'destroy'])->middleware('permission:prestations.delete')->name('prestations.destroy');
});
