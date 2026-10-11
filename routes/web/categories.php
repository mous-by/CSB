<?php

use App\Http\Controllers\Web\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

// Catégories des logiciels, du matériel et des actualités (phase 3) : la permission de modification du module
// concerné, vérifiée par CategoryPolicy selon la famille.
Route::middleware(['auth', 'active', 'password.changed', 'permission:logiciels.update|materiels.update|articles.update'])->group(function () {
    Route::get('/categories/{type}', [CategoryController::class, 'index'])->whereIn('type', ['logiciel', 'materiel', 'article'])->name('categories.index');
    Route::post('/categories/{type}', [CategoryController::class, 'store'])->whereIn('type', ['logiciel', 'materiel', 'article'])->name('categories.store');
    Route::put('/categories/element/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/element/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});
