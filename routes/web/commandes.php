<?php

use App\Http\Controllers\Web\Admin\CommandeController;
use Illuminate\Support\Facades\Route;

// Commandes de la boutique (cahier §15.3).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/commandes', [CommandeController::class, 'index'])->middleware('permission:boutique.view')->name('commandes.index');
    Route::get('/commandes/export', [CommandeController::class, 'export'])->middleware('permission:boutique.export')->name('commandes.export');
    Route::get('/commandes/{commande}', [CommandeController::class, 'show'])->middleware('permission:boutique.view')->name('commandes.show');
    Route::put('/commandes/{commande}/statut', [CommandeController::class, 'status'])->middleware('permission:boutique.update')->name('commandes.status');
    Route::delete('/commandes/{commande}', [CommandeController::class, 'destroy'])->middleware('permission:boutique.delete')->name('commandes.destroy');
});
