<?php

use App\Http\Controllers\Web\Admin\KitController;
use App\Http\Controllers\Web\Admin\PartenaireController;
use App\Http\Controllers\Web\Admin\PartnerSpaceController;
use Illuminate\Support\Facades\Route;

// Partenaires de diffusion, commissions, espace partenaire et kit de publication (demande de CSB, 2026-10-12).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/partenaires', [PartenaireController::class, 'index'])->middleware('permission:partenaires.view')->name('partenaires.index');
    Route::post('/partenaires', [PartenaireController::class, 'store'])->middleware('permission:partenaires.manage')->name('partenaires.store');
    Route::get('/partenaires/{partenaire}', [PartenaireController::class, 'show'])->middleware('permission:partenaires.view')->name('partenaires.show');
    Route::put('/partenaires/{partenaire}', [PartenaireController::class, 'update'])->middleware('permission:partenaires.manage')->name('partenaires.update');
    Route::post('/partenaires/{partenaire}/commissions', [PartenaireController::class, 'storeCommission'])->middleware('permission:partenaires.commissions')->name('partenaires.commissions.store');
    Route::post('/partenaires/{partenaire}/paiement', [PartenaireController::class, 'pay'])->middleware('permission:partenaires.commissions')->name('partenaires.pay');
    Route::put('/commissions/{commission}/annulation', [PartenaireController::class, 'cancelCommission'])->middleware('permission:partenaires.commissions')->name('commissions.cancel');

    Route::get('/mon-espace-partenaire', PartnerSpaceController::class)->middleware('permission:partenaires.dashboard')->name('partenaire.espace');

    Route::get('/kit', [KitController::class, 'index'])->middleware('permission:partenaires.kit')->name('kit.index');
    Route::get('/kit/{type}/{id}', [KitController::class, 'show'])->middleware('permission:partenaires.kit')->whereIn('type', ['materiel', 'logiciel', 'realisation', 'article'])->whereNumber('id')->name('kit.show');
    Route::get('/kit/{type}/{id}/visuel/{format}', [KitController::class, 'image'])->middleware('permission:partenaires.kit')->whereIn('type', ['materiel', 'logiciel', 'realisation', 'article'])->whereNumber('id')->name('kit.image');
});
