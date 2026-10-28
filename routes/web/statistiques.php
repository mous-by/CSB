<?php

use App\Http\Controllers\Web\Admin\StatistiquesController;
use Illuminate\Support\Facades\Route;

// Statistiques de fréquentation du site public (cahier §2).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/statistiques', [StatistiquesController::class, 'index'])->middleware('permission:statistiques.view')->name('statistiques.index');
});
