<?php

/*
| Manifeste du module « users » (D6, D7). Une clé par permission `module.action` ; `roles` donne, par rôle, les
| permissions par défaut (`defaults`, synchronisées par permissions:sync) et celles attribuables en exception
| (`optional`) ; `navigation` alimente le menu (les entrées dont la route n'existe pas encore sont ignorées).
*/

return [
    'label' => 'Utilisateurs',

    'permissions' => [
        'view' => 'Voir les utilisateurs',
        'create' => 'Créer un utilisateur',
        'update' => 'Modifier un utilisateur',
        'delete' => 'Supprimer un utilisateur',
        'activate' => 'Activer ou désactiver un utilisateur',
        'reset_password' => 'Réinitialiser un mot de passe',
        'revoke_access' => 'Révoquer les sessions et jetons',
    ],

    'reserved' => [],

    'roles' => [
        // Administrateur : comptes et droits (cahier §7.1). Le gestionnaire de contenu et le commercial ne gèrent aucun compte.
        'administrateur' => [
            'defaults' => ['view', 'create', 'update', 'activate', 'reset_password', 'revoke_access'],
            'optional' => ['delete'],
        ],
    ],

    // Pas d'entrée de sidebar : l'écran s'ouvre depuis le sous-menu Paramètres (configuration/_menu.blade.php),
    // qui la propose déjà, comme Audit et Système.
    'navigation' => [],
];
