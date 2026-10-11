<?php

use App\Http\Controllers\Web\Admin\TemoignageController;
use Illuminate\Support\Facades\Route;

// Témoignages (phase 1). Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/temoignages', [TemoignageController::class, 'index'])->middleware('permission:temoignages.view')->name('temoignages.index');
    Route::post('/temoignages', [TemoignageController::class, 'store'])->middleware('permission:temoignages.create')->name('temoignages.store');
    Route::put('/temoignages/{temoignage}', [TemoignageController::class, 'update'])->middleware('permission:temoignages.update')->name('temoignages.update');
    Route::delete('/temoignages/{temoignage}', [TemoignageController::class, 'destroy'])->middleware('permission:temoignages.delete')->name('temoignages.destroy');
});
