<?php

/*
| Manifeste du module « whatsapp » : alertes WhatsApp automatiques à l'équipe (cahier §15.3) à chaque nouvelle demande,
| commande ou ticket, via l'API officielle WhatsApp Business. Compte Meta et modèle de message : à fournir par CSB.
*/

return [
    'label' => 'Alertes WhatsApp',

    'permissions' => [
        'manage' => 'Régler les alertes WhatsApp (numéros à prévenir, événements) et envoyer un message de test',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['manage'], 'optional' => []],
        'commercial' => ['defaults' => [], 'optional' => ['manage']],
    ],

    'navigation' => [],
];
