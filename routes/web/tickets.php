<?php

use App\Http\Controllers\Web\Admin\TicketAppController;
use App\Http\Controllers\Web\Admin\TicketController;
use Illuminate\Support\Facades\Route;

// Tickets d'assistance des clients (cahier §15.3).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    // Applications connectées (jetons de portée « tickets ») : avant /tickets/{ticket}.
    Route::get('/tickets/applications', [TicketAppController::class, 'index'])->middleware('permission:tickets.apps')->name('tickets.apps.index');
    Route::post('/tickets/applications', [TicketAppController::class, 'store'])->middleware('permission:tickets.apps')->name('tickets.apps.store');
    Route::delete('/tickets/applications/{token}', [TicketAppController::class, 'revoke'])->middleware('permission:tickets.apps')->name('tickets.apps.revoke');
    Route::get('/tickets', [TicketController::class, 'index'])->middleware('permission:tickets.view')->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->middleware('permission:tickets.view')->name('tickets.show');
    Route::get('/tickets/{ticket}/messages/{message}/piece-jointe', [TicketController::class, 'attachment'])->middleware('permission:tickets.view')->name('tickets.attachment');
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'reply'])->middleware('permission:tickets.update')->name('tickets.reply');
    Route::put('/tickets/{ticket}/statut', [TicketController::class, 'status'])->middleware('permission:tickets.update')->name('tickets.status');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->middleware('permission:tickets.update')->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->middleware('permission:tickets.delete')->name('tickets.destroy');
});
