<?php

use App\Http\Controllers\Site\ActualiteController;
use App\Http\Controllers\Site\BoutiqueController;
use App\Http\Controllers\Site\DemandeController;
use App\Http\Controllers\Site\LogicielController;
use App\Http\Controllers\Site\MaterielController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\RealisationController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\SeoController;
use App\Http\Controllers\Site\TemoignageController;
use App\Http\Controllers\VideoUploadController;
use Illuminate\Support\Facades\Route;

// Site public (cahier §4) : sans authentification, routes nommées `site.*` (test d'architecture). Les formulaires
// sont limités en débit (throttle) et protégés contre le spam (cahier §10).
Route::name('site.')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/a-propos', [PageController::class, 'about'])->name('about');
    Route::get('/services', [PageController::class, 'services'])->name('services');
    Route::get('/logiciels', [LogicielController::class, 'index'])->name('logiciels.index');
    Route::get('/logiciels/{logiciel:slug}', [LogicielController::class, 'show'])->name('logiciels.show');
    Route::get('/materiel', [MaterielController::class, 'index'])->name('materiels.index');
    Route::get('/materiel/{materiel:slug}', [MaterielController::class, 'show'])->name('materiels.show');
    Route::get('/realisations', [RealisationController::class, 'index'])->name('realisations.index');
    Route::get('/realisations/{realisation:slug}', [RealisationController::class, 'show'])->name('realisations.show');
    // Boutique (cahier §15.3) : panier en session, commande, suivi par lien secret.
    Route::get('/panier', [BoutiqueController::class, 'panier'])->name('panier');
    Route::post('/panier/{materiel}', [BoutiqueController::class, 'add'])->middleware('throttle:60,1')->name('panier.add');
    Route::patch('/panier/{materiel}', [BoutiqueController::class, 'update'])->middleware('throttle:60,1')->name('panier.update');
    Route::delete('/panier/{materiel}', [BoutiqueController::class, 'remove'])->middleware('throttle:60,1')->name('panier.remove');
    Route::get('/commande', [BoutiqueController::class, 'checkout'])->name('commande');
    Route::post('/commande/code-promo', [BoutiqueController::class, 'promo'])->middleware('throttle:20,1')->name('commande.promo');
    Route::post('/commande', [BoutiqueController::class, 'store'])->middleware('throttle:demandes')->name('commande.store');
    Route::get('/commande/{number}/{token}', [BoutiqueController::class, 'suivi'])->where('token', '[A-Za-z0-9]{48}')->name('commande.suivi');
    // Témoignages (vidéo et écrit) ; envoi d'une vidéo par morceaux de 1 Mo (300 Mo au plus).
    Route::get('/temoignages', [TemoignageController::class, 'index'])->name('temoignages.index');
    Route::get('/temoignages/partager', [TemoignageController::class, 'create'])->name('temoignages.create');
    Route::post('/temoignages/partager', [TemoignageController::class, 'store'])->middleware('throttle:demandes')->name('temoignages.store');
    Route::post('/temoignages/video', VideoUploadController::class)->middleware('throttle:400,1')->name('temoignages.video');
    Route::get('/actualites', [ActualiteController::class, 'index'])->name('actualites.index');
    Route::get('/actualites/{article:slug}', [ActualiteController::class, 'show'])->name('actualites.show');

    // Demandes commerciales (cahier §6) : `demande_type` choisit les règles de DemandeRequest ; débit limité
    // (throttle:demandes, AppServiceProvider) et anti-spam (SpamGuard).
    Route::get('/contact', [DemandeController::class, 'contact'])->name('contact');
    Route::get('/devis', [DemandeController::class, 'devis'])->name('devis');
    Route::get('/demonstration/{logiciel:slug}', [DemandeController::class, 'demonstration'])->name('demonstration');
    Route::get('/disponibilite/{materiel:slug}', [DemandeController::class, 'disponibilite'])->name('disponibilite');
    Route::middleware('throttle:demandes')->group(function () {
        Route::post('/contact', [DemandeController::class, 'storeContact'])->defaults('demande_type', 'contact')->name('contact.store');
        Route::post('/devis', [DemandeController::class, 'storeDevis'])->defaults('demande_type', 'devis')->name('devis.store');
        Route::post('/demonstration/{logiciel:slug}', [DemandeController::class, 'storeDemonstration'])->defaults('demande_type', 'demonstration')->name('demonstration.store');
        Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
        Route::post('/disponibilite/{materiel:slug}', [DemandeController::class, 'storeDisponibilite'])->defaults('demande_type', 'disponibilite')->name('disponibilite.store');
    });

    Route::get('/newsletter/desinscription/{token}', [NewsletterController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{20,64}')->name('newsletter.unsubscribe');
    Route::get('/mentions-legales', [PageController::class, 'legal'])->name('legal');
    Route::get('/confidentialite', [PageController::class, 'privacy'])->name('privacy');

    // Recherche (page de résultats et suggestions en direct), référencement technique (cahier §11.1).
    Route::get('/recherche', [SearchController::class, 'index'])->name('search');
    Route::get('/recherche/suggestions', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
});

// Adresse inconnue : redirection d'une ancienne adresse de contenu (301), sinon 404 du site.
Route::fallback([SeoController::class, 'fallback'])->name('site.fallback');
