<?php

use App\Http\Controllers\Web\Admin\DemandeController;
use Illuminate\Support\Facades\Route;

// Demandes commerciales (phase 6). Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/demandes', [DemandeController::class, 'index'])->middleware('permission:demandes.view')->name('demandes.index');
    Route::get('/demandes/export', [DemandeController::class, 'export'])->middleware('permission:demandes.export')->name('demandes.export');
    Route::get('/demandes/{demande}', [DemandeController::class, 'show'])->middleware('permission:demandes.view')->name('demandes.show');
    Route::get('/demandes/{demande}/piece-jointe', [DemandeController::class, 'attachment'])->middleware('permission:demandes.view')->name('demandes.attachment');
    Route::put('/demandes/{demande}/statut', [DemandeController::class, 'status'])->middleware('permission:demandes.update')->name('demandes.status');
    Route::put('/demandes/{demande}/attribution', [DemandeController::class, 'assign'])->middleware('permission:demandes.update')->name('demandes.assign');
    Route::post('/demandes/{demande}/notes', [DemandeController::class, 'note'])->middleware('permission:demandes.update')->name('demandes.notes.store');
    Route::post('/demandes/{demande}/notification', [DemandeController::class, 'resend'])->middleware('permission:demandes.update')->name('demandes.notify');
    Route::delete('/demandes/{demande}', [DemandeController::class, 'destroy'])->middleware('permission:demandes.delete')->name('demandes.destroy');
});
