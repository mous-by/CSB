<?php

/*
| Manifeste du module « aide » : guide d'utilisation de l'administration en ligne (livrable « documentation »,
| cahier §14, §15.1 « Transfert »), un chapitre par tâche courante.
*/

return [
    'label' => 'Aide',

    'permissions' => [
        'view' => 'Consulter le guide d\'utilisation',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view'], 'optional' => []],
        'commercial' => ['defaults' => ['view'], 'optional' => []],
    ],

    'navigation' => [
        ['label' => 'Aide', 'icon' => 'bx bx-help-circle', 'route' => 'aide.index', 'permission' => 'aide.view', 'active' => 'aide.*', 'order' => 850, 'position' => 'bottom'],
    ],
];
