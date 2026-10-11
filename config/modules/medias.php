<?php

/*
| Manifeste du module « medias » : bibliothèque d'images (cahier §7 « Téléverser et organiser les images avec
| validation de type et de taille »).
*/

return [
    'label' => 'Médias',

    'permissions' => [
        'view' => 'Voir la bibliothèque d\'images',
        'upload' => 'Téléverser des images',
        'update' => 'Modifier le texte alternatif et le dossier d\'une image',
        'delete' => 'Supprimer une image inutilisée',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'upload', 'update', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'upload', 'update'], 'optional' => ['delete']],
    ],

    'navigation' => [
        ['label' => 'Médias', 'icon' => 'bx bx-images', 'route' => 'medias.index', 'permission' => 'medias.view', 'active' => 'medias.*', 'order' => 80],
    ],
];
