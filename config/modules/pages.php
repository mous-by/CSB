<?php

/*
| Manifeste du module « pages » : pages et paramètres du site (cahier §7 « Modifier les contenus institutionnels,
| coordonnées, slogan, liens sociaux et informations du pied de page »). `update` : textes des pages (accueil, à
| propos, pages légales) ; `settings` : paramètres sensibles (entreprise, coordonnées, réseaux, référencement),
| réservés à l'administrateur sauf autorisation explicite du gestionnaire de contenu (cahier §7.1).
*/

return [
    'label' => 'Pages et paramètres',

    'permissions' => [
        'update' => 'Modifier les textes des pages (accueil, à propos, pages légales)',
        'settings' => 'Modifier les paramètres du site (entreprise, coordonnées, réseaux sociaux, référencement)',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['update', 'settings'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['update'], 'optional' => ['settings']],
    ],

    'navigation' => [
        ['label' => 'Pages et paramètres', 'icon' => 'bx bx-file', 'route' => 'pages.edit', 'permission' => 'pages.update', 'active' => 'pages.*', 'order' => 10],
    ],
];
