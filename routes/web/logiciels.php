<?php

use App\Http\Controllers\Web\Admin\LogicielController;
use Illuminate\Support\Facades\Route;

// Logiciels (phase 3). Formulaire sur une page dédiée (fiche longue, CLAUDE.md §7). Toutes les routes portent
// permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/logiciels', [LogicielController::class, 'index'])->middleware('permission:logiciels.view')->name('logiciels.index');
    Route::get('/logiciels/create', [LogicielController::class, 'create'])->middleware('permission:logiciels.create')->name('logiciels.create');
    Route::post('/logiciels', [LogicielController::class, 'store'])->middleware('permission:logiciels.create')->name('logiciels.store');
    Route::get('/logiciels/{logiciel}/edit', [LogicielController::class, 'edit'])->middleware('permission:logiciels.update')->name('logiciels.edit');
    Route::put('/logiciels/{logiciel}', [LogicielController::class, 'update'])->middleware('permission:logiciels.update')->name('logiciels.update');
    Route::post('/logiciels/{logiciel}/publication', [LogicielController::class, 'publication'])->middleware('permission:logiciels.publish')->name('logiciels.publication');
    Route::delete('/logiciels/{logiciel}', [LogicielController::class, 'destroy'])->middleware('permission:logiciels.delete')->name('logiciels.destroy');
});
