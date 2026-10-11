<?php

/*
| Manifeste du module « demandes » : demandes commerciales reçues par le site (cahier §6, §7 « Consulter, rechercher,
| filtrer, changer les statuts et ajouter des notes internes »). Rôle du commercial (cahier §7.1) ; le gestionnaire
| de contenu peut recevoir la consultation en exception.
*/

return [
    'label' => 'Demandes',

    'permissions' => [
        'view' => 'Consulter les demandes (et télécharger leurs pièces jointes)',
        'update' => 'Traiter une demande : statut, attribution, notes internes',
        'export' => 'Exporter les demandes (CSV pour Excel)',
        'delete' => 'Supprimer une demande (spam, doublon)',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'update', 'export', 'delete'], 'optional' => []],
        'commercial' => ['defaults' => ['view', 'update', 'export'], 'optional' => ['delete']],
        'gestionnaire' => ['defaults' => [], 'optional' => ['view']],
    ],

    'navigation' => [
        ['label' => 'Demandes', 'icon' => 'bx bx-envelope', 'route' => 'demandes.index', 'permission' => 'demandes.view', 'active' => 'demandes.*', 'order' => 5],
    ],
];
