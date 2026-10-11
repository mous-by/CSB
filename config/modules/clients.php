<?php

/*
| Manifeste du module « clients » : comptes de l'espace client (cahier §15.3) et documents mis à leur disposition.
*/

return [
    'label' => 'Clients',

    'permissions' => [
        'view' => 'Voir les clients et leur espace (demandes, commandes, tickets, documents)',
        'create' => 'Créer des comptes clients (un par un, en lot ou par fichier)',
        'update' => 'Modifier un client, l\'activer ou le désactiver, lui envoyer un lien de mot de passe',
        'documents' => 'Déposer ou retirer des documents (devis, factures…) dans l\'espace d\'un client',
        'delete' => 'Supprimer un compte client',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'create', 'update', 'documents', 'delete'], 'optional' => []],
        'commercial' => ['defaults' => ['view', 'create', 'update', 'documents'], 'optional' => ['delete']],
    ],

    'navigation' => [
        ['label' => 'Clients', 'icon' => 'bx bx-user-pin', 'route' => 'clients.index', 'permission' => 'clients.view', 'active' => 'clients.*', 'order' => 6],
    ],
];
