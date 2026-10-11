<?php

use App\Http\Controllers\Web\Admin\BulkController;
use Illuminate\Support\Facades\Route;

// Saisie en masse : pour chaque module de config/bulk.php, la grille (/<module>/lot), l'enregistrement, l'import de
// fichier et le modèle Excel, derrière la permission de création du module.
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    foreach (config('bulk', []) as $module => $class) {
        Route::middleware('permission:'.app($class)->permission())->group(function () use ($module) {
            Route::get("/{$module}/lot", [BulkController::class, 'create'])->defaults('bulk_module', $module)->name("{$module}.bulk");
            Route::post("/{$module}/lot", [BulkController::class, 'store'])->defaults('bulk_module', $module)->name("{$module}.bulk.store");
            Route::post("/{$module}/import", [BulkController::class, 'import'])->defaults('bulk_module', $module)->name("{$module}.import");
            Route::get("/{$module}/import/modele", [BulkController::class, 'template'])->defaults('bulk_module', $module)->name("{$module}.import.template");
        });
    }
});
