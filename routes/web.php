<?php

use Illuminate\Support\Facades\Route;

// Administration (cahier §7 : « URL dédiée ») : un fichier par module dans routes/web/*.php (D7), chargés
// automatiquement dans l'ordre alphabétique sous le préfixe d'URL ADMIN_PATH (/admin par défaut). Les noms de route
// ne portent pas ce préfixe (`home` = tableau de bord, `login`, `users.index`…).
Route::prefix(config('site.admin_path'))->group(function () {
    $files = glob(__DIR__.'/web/*.php') ?: [];
    sort($files);

    foreach ($files as $file) {
        require $file;
    }
});

// Espace client (/espace-client, garde `client`).
require __DIR__.'/espace-client.php';

// Site public (cahier §4) : routes nommées `site.*`, sans authentification. Chargé après l'administration.
require __DIR__.'/site.php';

// /up (santé) est déclarée dans bootstrap/app.php.
