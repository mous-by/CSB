<?php

use App\Http\Controllers\Web\Admin\ArticleController;
use Illuminate\Support\Facades\Route;

// Actualités (phase 5). Formulaire sur une page dédiée (fiche longue, CLAUDE.md §7). Toutes les routes portent
// permission:module.action (jamais role:).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->middleware('permission:articles.view')->name('articles.index');
    Route::get('/articles/create', [ArticleController::class, 'create'])->middleware('permission:articles.create')->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->middleware('permission:articles.create')->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->middleware('permission:articles.update')->name('articles.edit');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->middleware('permission:articles.update')->name('articles.update');
    Route::post('/articles/{article}/publication', [ArticleController::class, 'publication'])->middleware('permission:articles.publish')->name('articles.publication');
    Route::post('/articles/markdown', [ArticleController::class, 'markdown'])->middleware('permission:articles.create|articles.update')->name('articles.markdown');
    Route::get('/articles/{article}/apercu', [ArticleController::class, 'preview'])->middleware('permission:articles.view')->name('articles.preview');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->middleware('permission:articles.delete')->name('articles.destroy');
});
