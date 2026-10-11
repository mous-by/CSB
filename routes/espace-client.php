<?php

use App\Http\Controllers\Client\AccountController;
use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\CommandeController;
use App\Http\Controllers\Client\TicketController;
use Illuminate\Support\Facades\Route;

// Espace client (cahier §15.3) : routes `client.*` sous /espace-client, garde `client` (test d'architecture).
Route::prefix('espace-client')->name('client.')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('/connexion', [AuthController::class, 'login'])->name('login');
        Route::post('/connexion', [AuthController::class, 'authenticate'])->middleware('throttle:20,1')->name('login.store');
        Route::get('/inscription', [AuthController::class, 'register'])->name('register');
        Route::post('/inscription', [AuthController::class, 'store'])->middleware('throttle:demandes')->name('register.store');
        Route::get('/mot-de-passe-oublie', [AuthController::class, 'forgot'])->name('password.request');
        Route::post('/mot-de-passe-oublie', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reinitialisation/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
        Route::post('/reinitialisation', [AuthController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    });

    // Lien reçu par e-mail : signé, valable connecté ou non.
    Route::get('/verification/{client}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:20,1'])->name('verification.verify');

    Route::middleware(['auth:client', 'client.active'])->group(function () {
        Route::get('/', [AccountController::class, 'home'])->name('home');
        Route::get('/demandes', [AccountController::class, 'demandes'])->name('demandes');
        Route::get('/documents', [AccountController::class, 'documents'])->name('documents');
        Route::get('/documents/{document}', [AccountController::class, 'download'])->name('documents.download');
        Route::get('/commandes', [CommandeController::class, 'index'])->name('commandes');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets');
        Route::get('/tickets/nouveau', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [TicketController::class, 'store'])->middleware('throttle:10,1')->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/messages', [TicketController::class, 'reply'])->middleware('throttle:20,1')->name('tickets.reply');
        Route::get('/tickets/{ticket}/messages/{message}/piece-jointe', [TicketController::class, 'attachment'])->name('tickets.attachment');
        Route::get('/profil', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profil', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/mot-de-passe', [AccountController::class, 'updatePassword'])->name('password.change');
        Route::post('/verification', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.send');
        Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
    });
});
