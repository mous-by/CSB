<?php

use App\Http\Controllers\Web\Admin\PageSettingsController;
use Illuminate\Support\Facades\Route;

// Pages et paramètres du site (phase 1). Le contrôleur vérifie la permission de l'onglet (pages.update ou pages.settings).
Route::middleware(['auth', 'active', 'password.changed', 'permission:pages.update|pages.settings'])->group(function () {
    Route::get('/pages', [PageSettingsController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{group}', [PageSettingsController::class, 'update'])->name('pages.update');
});
