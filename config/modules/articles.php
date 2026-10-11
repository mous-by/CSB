<?php

/*
| Manifeste du module « articles » : actualités et conseils (cahier §4, §7 « Rédiger, prévisualiser, publier, dépublier
| et catégoriser les articles »). Les catégories d'actualités relèvent de `update`.
*/

return [
    'label' => 'Actualités',

    'permissions' => [
        'view' => 'Voir les articles (et les prévisualiser)',
        'create' => 'Rédiger un article',
        'update' => 'Modifier un article (et gérer les catégories d\'actualités)',
        'publish' => 'Publier, programmer ou dépublier un article',
        'delete' => 'Supprimer un article',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'publish', 'delete'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'create', 'update', 'publish'], 'optional' => ['delete']],
    ],

    'navigation' => [
        ['label' => 'Actualités', 'icon' => 'bx bx-news', 'route' => 'articles.index', 'permission' => 'articles.view', 'active' => ['articles.*'], 'order' => 60],
    ],
];
