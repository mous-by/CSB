<?php

use App\Http\Controllers\Web\Admin\RealisationController;
use Illuminate\Support\Facades\Route;

// Réalisations (phase 5). Formulaire sur une page dédiée (fiche longue, CLAUDE.md §7). Toutes les routes portent
// permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/realisations', [RealisationController::class, 'index'])->middleware('permission:realisations.view')->name('realisations.index');
    Route::get('/realisations/create', [RealisationController::class, 'create'])->middleware('permission:realisations.create')->name('realisations.create');
    Route::post('/realisations', [RealisationController::class, 'store'])->middleware('permission:realisations.create')->name('realisations.store');
    Route::get('/realisations/{realisation}/edit', [RealisationController::class, 'edit'])->middleware('permission:realisations.update')->name('realisations.edit');
    Route::put('/realisations/{realisation}', [RealisationController::class, 'update'])->middleware('permission:realisations.update')->name('realisations.update');
    Route::post('/realisations/{realisation}/publication', [RealisationController::class, 'publication'])->middleware('permission:realisations.publish')->name('realisations.publication');
    Route::delete('/realisations/{realisation}', [RealisationController::class, 'destroy'])->middleware('permission:realisations.delete')->name('realisations.destroy');
});
