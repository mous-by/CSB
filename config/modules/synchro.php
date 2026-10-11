<?php

/*
| Manifeste du module « synchro » : synchronisation du stock et des prix du matériel avec le logiciel de stock de CSB
| (cahier §15.3), par API à jeton ou par fichier Excel / CSV. Chaque synchronisation est journalisée.
*/

return [
    'label' => 'Synchronisation du stock',

    'permissions' => [
        'view' => 'Voir l\'état et le journal des synchronisations du stock',
        'import' => 'Importer un fichier de stock (Excel ou CSV)',
        'tokens' => 'Créer et révoquer les jetons d\'accès de l\'API de synchronisation',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'import', 'tokens'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'import'], 'optional' => []],
    ],

    'navigation' => [
        ['label' => 'Synchronisation du stock', 'icon' => 'bx bx-sync', 'route' => 'synchro.index', 'permission' => 'synchro.view', 'active' => 'synchro.*', 'order' => 45],
    ],
];
