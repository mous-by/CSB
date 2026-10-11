<?php

use App\Http\Controllers\Web\Admin\AbonneController;
use Illuminate\Support\Facades\Route;

// Abonnés aux actualités. Toutes les routes portent permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/abonnes', [AbonneController::class, 'index'])->middleware('permission:abonnes.view')->name('abonnes.index');
    Route::get('/abonnes/export', [AbonneController::class, 'export'])->middleware('permission:abonnes.export')->name('abonnes.export');
    Route::post('/abonnes/{abonne}/desinscription', [AbonneController::class, 'unsubscribe'])->middleware('permission:abonnes.delete')->name('abonnes.unsubscribe');
    Route::delete('/abonnes/{abonne}', [AbonneController::class, 'destroy'])->middleware('permission:abonnes.delete')->name('abonnes.destroy');
    Route::post('/articles/{article}/newsletter', [AbonneController::class, 'send'])->middleware('permission:abonnes.send')->name('articles.newsletter');
});
