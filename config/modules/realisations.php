<?php

/*
| Manifeste du module « realisations » : projets publiés (cahier §4, §7 « Gérer les projets, descriptions, visuels,
| secteurs et ordre d'affichage »).
*/

return [
    'label' => 'Réalisations',

    'permissions' => [
        'view' => 'Voir les réalisations',
        'create' => 'Créer une réalisation',
        'update' => 'Modifier une réalisation',
        'publish' => 'Publier ou dépublier une réalisation (autorisation du client obtenue)',
        'delete' => 'Supprimer une réalisation',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'publish', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update', 'publish'], 'optional' => ['delete']],
    ],

    'navigation' => [
        ['label' => 'Réalisations', 'icon' => 'bx bx-briefcase-alt-2', 'route' => 'realisations.index', 'permission' => 'realisations.view', 'active' => ['realisations.*'], 'order' => 50],
    ],
];
