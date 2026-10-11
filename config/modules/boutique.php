<?php

/*
| Manifeste du module « boutique » : commandes passées sur le site (cahier §15.3 « panier et paiement »). Paiement
| manuel (espèces, Orange Money, Moov Money, Wave) confirmé par l'équipe ; prestataire de paiement en ligne : À VALIDER.
*/

return [
    'label' => 'Boutique',

    'permissions' => [
        'view' => 'Consulter les commandes de la boutique',
        'update' => 'Traiter une commande : confirmation, paiement reçu (référence), livraison, annulation',
        'export' => 'Exporter les commandes (CSV pour Excel)',
        'delete' => 'Supprimer une commande (doublon, test)',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'update', 'export', 'delete'], 'optional' => []],
        'commercial' => ['defaults' => ['view', 'update', 'export'], 'optional' => ['delete']],
        'gestionnaire' => ['defaults' => [], 'optional' => ['view']],
    ],

    'navigation' => [
        ['label' => 'Commandes', 'icon' => 'bx bx-cart', 'route' => 'commandes.index', 'permission' => 'boutique.view', 'active' => 'commandes.*', 'order' => 6],
    ],
];
