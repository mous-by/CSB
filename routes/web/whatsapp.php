<?php

use App\Http\Controllers\Web\Admin\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Alertes WhatsApp à l'équipe (cahier §15.3), menu Paramètres.
Route::middleware(['auth', 'active', 'password.changed', 'permission:whatsapp.manage'])->group(function () {
    Route::get('/parametres/whatsapp', [WhatsAppController::class, 'edit'])->name('whatsapp.settings');
    Route::put('/parametres/whatsapp', [WhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::post('/parametres/whatsapp/test', [WhatsAppController::class, 'test'])->middleware('throttle:5,1')->name('whatsapp.test');
});
