<?php

use App\Http\Controllers\Api\StockSyncController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

// API de synchronisation du stock (cahier §15.3), sous /api : jeton d'accès (Paramètres → Synchronisation) et débit limité.
Route::name('api.')->prefix('v1')->middleware(['throttle:60,1', 'sync.token'])->group(function () {
    Route::get('/stock', [StockSyncController::class, 'index'])->name('stock.index');
    Route::post('/stock', [StockSyncController::class, 'store'])->name('stock.sync');
});

// Tickets des applications connectées (KalanNet, DojoManager…) : jeton de portée « tickets » (Tickets → Applications).
Route::name('api.')->prefix('v1')->middleware(['throttle:60,1', 'sync.token:tickets'])->group(function () {
    Route::get('/tickets/options', [TicketController::class, 'options'])->name('tickets.options');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{number}', [TicketController::class, 'show'])->where('number', 'TCK-\d{4}-\d+')->name('tickets.show');
    Route::post('/tickets/{number}/messages', [TicketController::class, 'reply'])->where('number', 'TCK-\d{4}-\d+')->name('tickets.reply');
    Route::get('/tickets/{number}/messages/{message}/piece-jointe', [TicketController::class, 'attachment'])->where('number', 'TCK-\d{4}-\d+')->name('tickets.attachment');
});
