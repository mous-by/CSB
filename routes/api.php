<?php

use App\Http\Controllers\Api\StockSyncController;
use Illuminate\Support\Facades\Route;

// API de synchronisation du stock (cahier §15.3), sous /api : jeton d'accès (Paramètres → Synchronisation) et débit limité.
Route::name('api.')->prefix('v1')->middleware(['throttle:60,1', 'sync.token'])->group(function () {
    Route::get('/stock', [StockSyncController::class, 'index'])->name('stock.index');
    Route::post('/stock', [StockSyncController::class, 'store'])->name('stock.sync');
});
