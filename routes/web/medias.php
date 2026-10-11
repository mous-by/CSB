<?php

use App\Http\Controllers\Web\Admin\MediaController;
use Illuminate\Support\Facades\Route;

// Bibliothèque d'images (phase 2). Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/medias', [MediaController::class, 'index'])->middleware('permission:medias.view')->name('medias.index');
    Route::get('/medias/search', [MediaController::class, 'search'])->middleware('permission:medias.view')->name('medias.search');
    Route::post('/medias', [MediaController::class, 'store'])->middleware('permission:medias.upload')->name('medias.store');
    Route::put('/medias/{media}', [MediaController::class, 'update'])->middleware('permission:medias.update')->name('medias.update');
    Route::delete('/medias/{media}', [MediaController::class, 'destroy'])->middleware('permission:medias.delete')->name('medias.destroy');
});
