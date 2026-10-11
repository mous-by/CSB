<?php

use App\Http\Controllers\Web\Admin\SynchroController;
use Illuminate\Support\Facades\Route;

// Synchronisation du stock (cahier §15.3).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/synchro', [SynchroController::class, 'index'])->middleware('permission:synchro.view')->name('synchro.index');
    Route::get('/synchro/modele', [SynchroController::class, 'template'])->middleware('permission:synchro.import')->name('synchro.template');
    Route::post('/synchro/import', [SynchroController::class, 'import'])->middleware('permission:synchro.import')->name('synchro.import');
    Route::post('/synchro/jetons', [SynchroController::class, 'storeToken'])->middleware('permission:synchro.tokens')->name('synchro.tokens.store');
    Route::delete('/synchro/jetons/{token}', [SynchroController::class, 'revokeToken'])->middleware('permission:synchro.tokens')->name('synchro.tokens.revoke');
});
