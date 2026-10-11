<?php

use App\Http\Controllers\Web\Admin\MaterielController;
use Illuminate\Support\Facades\Route;

// Matériel (phase 4). Formulaire sur une page dédiée (fiche longue, CLAUDE.md §7). Toutes les routes portent
// permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/materiels', [MaterielController::class, 'index'])->middleware('permission:materiels.view')->name('materiels.index');
    Route::get('/materiels/create', [MaterielController::class, 'create'])->middleware('permission:materiels.create')->name('materiels.create');
    Route::post('/materiels', [MaterielController::class, 'store'])->middleware('permission:materiels.create')->name('materiels.store');
    Route::get('/materiels/{materiel}/edit', [MaterielController::class, 'edit'])->middleware('permission:materiels.update')->name('materiels.edit');
    Route::put('/materiels/{materiel}', [MaterielController::class, 'update'])->middleware('permission:materiels.update')->name('materiels.update');
    Route::post('/materiels/{materiel}/publication', [MaterielController::class, 'publication'])->middleware('permission:materiels.publish')->name('materiels.publication');
    Route::delete('/materiels/{materiel}', [MaterielController::class, 'destroy'])->middleware('permission:materiels.delete')->name('materiels.destroy');
});
