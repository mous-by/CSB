<?php

/*
| Manifeste du module « temoignages » : témoignages de clients autorisés, affichés sur l'accueil (cahier §4).
*/

return [
    'label' => 'Témoignages',

    'permissions' => [
        'view' => 'Voir les témoignages',
        'create' => 'Créer un témoignage',
        'update' => 'Modifier un témoignage (et le publier ou le masquer)',
        'delete' => 'Supprimer un témoignage',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update'], 'optional' => ['delete']],
        'communication' => ['defaults' => ['view'], 'optional' => ['create']],
    ],

    'navigation' => [
        ['label' => 'Témoignages', 'icon' => 'bx bx-message-square-dots', 'route' => 'temoignages.index', 'permission' => 'temoignages.view', 'active' => 'temoignages.*', 'order' => 70],
    ],
];
