<?php

/*
| Manifeste du module « logiciels » : catalogue des solutions logicielles (cahier §5.1, §5.2, §7 « Créer, modifier,
| publier, dépublier et archiver des fiches et leurs visuels »). Les catégories (secteurs) relèvent de `update`.
*/

return [
    'label' => 'Logiciels',

    'permissions' => [
        'view' => 'Voir le catalogue des logiciels',
        'create' => 'Créer une fiche logiciel',
        'update' => 'Modifier une fiche logiciel (et gérer les catégories de logiciels)',
        'publish' => 'Publier, dépublier ou archiver un logiciel',
        'delete' => 'Supprimer une fiche logiciel',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'publish', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update', 'publish'], 'optional' => ['delete']],
        'communication' => ['defaults' => ['view'], 'optional' => []],
    ],

    'navigation' => [
        ['label' => 'Logiciels', 'icon' => 'bx bx-code-block', 'route' => 'logiciels.index', 'permission' => 'logiciels.view', 'active' => ['logiciels.*'], 'order' => 30],
    ],
];
