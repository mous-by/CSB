<?php

/*
| Manifeste du module « prestations » : services de CSB présentés sur la page Services et l'accueil (cahier §4).
*/

return [
    'label' => 'Services',

    'permissions' => [
        'view' => 'Voir les services',
        'create' => 'Créer un service',
        'update' => 'Modifier un service (et le publier ou le masquer)',
        'delete' => 'Supprimer un service',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update'], 'optional' => ['delete']],
    ],

    'navigation' => [
        ['label' => 'Services', 'icon' => 'bx bx-wrench', 'route' => 'prestations.index', 'permission' => 'prestations.view', 'active' => 'prestations.*', 'order' => 20],
    ],
];
