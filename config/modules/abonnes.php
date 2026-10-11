<?php

/*
| Manifeste du module « abonnes » : abonnés aux actualités (pied de page du site) et envoi d'un article aux abonnés.
*/

return [
    'label' => 'Abonnés',

    'permissions' => [
        'view' => 'Voir les abonnés aux actualités',
        'create' => 'Ajouter des abonnés (un par un, en lot ou par fichier)',
        'export' => 'Exporter les abonnés (CSV pour Excel)',
        'delete' => 'Supprimer ou désinscrire un abonné',
        'send' => 'Envoyer un article aux abonnés',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'export', 'delete', 'send'], 'optional' => []],
        'gestionnaire' => ['defaults' => ['view', 'send'], 'optional' => ['create', 'export', 'delete']],
        'commercial' => ['defaults' => ['view', 'export'], 'optional' => ['create']],
    ],

    'navigation' => [
        ['label' => 'Abonnés', 'icon' => 'bx bx-mail-send', 'route' => 'abonnes.index', 'permission' => 'abonnes.view', 'active' => 'abonnes.*', 'order' => 8],
    ],
];
