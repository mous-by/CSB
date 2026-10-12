<?php

/*
| Manifeste du module « partenaires » : partenaires de diffusion (réseaux sociaux), lien personnel et code promo,
| visites et ventes attribuées, commissions à taux réglables, kit de publication (demande de CSB, 2026-10-12).
*/

return [
    'label' => 'Partenaires',

    'permissions' => [
        'view' => 'Voir les partenaires, leurs statistiques et leurs commissions',
        'manage' => 'Créer et modifier les partenaires : lien, code promo, taux de commission, compte',
        'commissions' => 'Enregistrer, payer et annuler les commissions',
        'kit' => 'Utiliser le kit de publication (textes et visuels prêts à poster)',
        'dashboard' => 'Voir son propre espace partenaire (lien, résultats, commissions)',
    ],

    'reserved' => [],

    'roles' => [
        'administrateur' => ['defaults' => ['view', 'manage', 'commissions', 'kit'], 'optional' => []],
        'commercial' => ['defaults' => ['view'], 'optional' => ['commissions', 'kit']],
        'gestionnaire' => ['defaults' => ['kit'], 'optional' => ['view']],
        'communication' => ['defaults' => ['dashboard', 'kit'], 'optional' => []],
    ],

    'navigation' => [
        ['label' => 'Partenaires', 'icon' => 'bx bx-share-alt', 'route' => 'partenaires.index', 'permission' => 'partenaires.view', 'active' => 'partenaires.*', 'order' => 8],
        ['label' => 'Mon espace partenaire', 'icon' => 'bx bx-trending-up', 'route' => 'partenaire.espace', 'permission' => 'partenaires.dashboard', 'active' => 'partenaire.*', 'order' => 1],
        ['label' => 'Kit de publication', 'icon' => 'bx bx-share', 'route' => 'kit.index', 'permission' => 'partenaires.kit', 'active' => 'kit.*', 'order' => 2],
    ],
];
