<?php

/*
|--------------------------------------------------------------------------
| Modules prévus, NON implémentés (D25)
|--------------------------------------------------------------------------
|
| Deux familles : les modules de la V1 encore à construire (cahier §7, ils disparaissent de cette liste dès que leur
| manifeste config/modules/<clé>.php existe) et les évolutions possibles (cahier §15.3), hors périmètre V1
| (cahier §3.2) : la base technique doit permettre de les ajouter plus tard, sans les imposer.
|
| `roles` : rôles qui verront ce module (superadmin et administrateur voient tout). `cahier` : où le cahier en
| parle. `open` : décisions à prendre avant de le construire.
|
*/

// Toutes les évolutions du cahier §15.3 retenues par CSB sont construites (2026-10-11) : espace client, boutique,
// synchronisation du stock, alertes WhatsApp, tickets. La version anglaise a été abandonnée à la demande de CSB.
// Un futur module s'ajoute ici (même format : label, icon, order, roles, summary, cahier, open) jusqu'à son manifeste.
return [];
