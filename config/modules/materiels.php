<?php

/*
| Manifeste du module « materiels » : catalogue de matériel informatique (cahier §5.3, §5.4, §7 « Gérer catégories,
| produits, caractéristiques, prix, état et disponibilité déclarée »). Les catégories relèvent de `update`.
*/

return [
    'label' => 'Matériel',

    'permissions' => [
        'view' => 'Voir le catalogue de matériel',
        'create' => 'Créer une fiche matériel',
        'update' => 'Modifier une fiche matériel : prix, état, disponibilité (et gérer les catégories de matériel)',
        'publish' => 'Publier ou dépublier un matériel',
        'delete' => 'Supprimer une fiche matériel',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'publish', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update', 'publish'], 'optional' => ['delete']],
        'communication' => ['defaults' => ['view'], 'optional' => []],
    ],

    'navigation' => [
        ['label' => 'Matériel', 'icon' => 'bx bx-laptop', 'route' => 'materiels.index', 'permission' => 'materiels.view', 'active' => ['materiels.*'], 'order' => 40],
    ],
];
