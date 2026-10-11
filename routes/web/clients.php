<?php

use App\Http\Controllers\Web\Admin\ClientController;
use Illuminate\Support\Facades\Route;

// Clients de l'espace client. Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view')->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create')->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view')->name('clients.show');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.update')->name('clients.update');
    Route::post('/clients/{client}/lien-mot-de-passe', [ClientController::class, 'sendPasswordLink'])->middleware('permission:clients.update')->name('clients.password-link');
    Route::post('/clients/{client}/documents', [ClientController::class, 'addDocument'])->middleware('permission:clients.documents')->name('clients.documents.store');
    Route::get('/clients/{client}/documents/{document}', [ClientController::class, 'downloadDocument'])->middleware('permission:clients.view')->name('clients.documents.download');
    Route::delete('/clients/{client}/documents/{document}', [ClientController::class, 'removeDocument'])->middleware('permission:clients.documents')->name('clients.documents.destroy');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete')->name('clients.destroy');
});
