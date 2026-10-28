<?php

use App\Http\Controllers\Web\Admin\PushController;
use Illuminate\Support\Facades\Route;

// Notifications push sur les appareils de l'équipe (tickets).
Route::middleware(['auth', 'active', 'password.changed', 'permission:tickets.view', 'throttle:30,1'])->group(function () {
    Route::post('/notifications-push', [PushController::class, 'store'])->name('push.store');
    Route::delete('/notifications-push', [PushController::class, 'destroy'])->name('push.destroy');
    Route::post('/notifications-push/essai', [PushController::class, 'test'])->name('push.test');
});
