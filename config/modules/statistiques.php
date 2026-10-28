<?php

/*
| Manifeste du module « statistiques » : fréquentation du site public (visiteurs, pages vues, provenance, appareils)
| et conversion en comptes clients et en demandes (cahier §2 « objectifs mesurables », demande de CSB 2026-10-28).
*/

return [
    'label' => 'Statistiques du site',

    'permissions' => [
        'view' => 'Voir la fréquentation du site : visiteurs, pages vues, provenance, comptes clients créés, conversion',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view'], 'optional' => []],
        'commercial' => ['defaults' => [], 'optional' => ['view']],
        'gestionnaire' => ['defaults' => [], 'optional' => ['view']],
        'communication' => ['defaults' => [], 'optional' => ['view']],
    ],

    'navigation' => [
        ['label' => 'Statistiques du site', 'icon' => 'bx bx-line-chart', 'route' => 'statistiques.index', 'permission' => 'statistiques.view', 'active' => 'statistiques.*', 'order' => 9],
    ],
];
