<?php

/*
| Manifeste du module « tickets » : assistance des clients (cahier §15.3 « tickets de support »). Le client ouvre un
| ticket depuis son espace, l'utilisateur d'une application connectée (KalanNet…) depuis l'application ; l'équipe répond, ajoute des notes internes, change le statut, la priorité et l'attribution.
*/

return [
    'label' => 'Tickets',

    'permissions' => [
        'view' => 'Consulter les tickets d\'assistance (et leurs pièces jointes)',
        'update' => 'Traiter un ticket : réponse au client, note interne, statut, priorité, attribution',
        'delete' => 'Supprimer un ticket (doublon, erreur)',
        'apps' => 'Connecter les applications de CSB (KalanNet…) : créer et révoquer leurs jetons d\'accès aux tickets',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'update', 'delete', 'apps'], 'optional' => []],
        'commercial' => ['defaults' => ['view', 'update'], 'optional' => ['delete']],
        'gestionnaire' => ['defaults' => [], 'optional' => ['view', 'update']],
    ],

    'navigation' => [
        ['label' => 'Tickets', 'icon' => 'bx bx-support', 'route' => 'tickets.index', 'permission' => 'tickets.view', 'active' => 'tickets.*', 'order' => 7],
    ],
];
