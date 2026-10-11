<?php

/*
| Saisie en masse (« ajouter des lignes », « ajouter un fichier ») : module => définition (App\Services\Bulk).
| Chaque entrée donne la page /admin/<module>/lot, le modèle Excel et l'import de fichier (routes/web/bulk.php).
*/

use App\Services\Bulk\Definitions\AbonneBulk;
use App\Services\Bulk\Definitions\CategoryBulk;
use App\Services\Bulk\Definitions\ClientBulk;
use App\Services\Bulk\Definitions\LogicielBulk;
use App\Services\Bulk\Definitions\MaterielBulk;
use App\Services\Bulk\Definitions\PrestationBulk;
use App\Services\Bulk\Definitions\RealisationBulk;
use App\Services\Bulk\Definitions\TemoignageBulk;

return [
    'prestations' => PrestationBulk::class,
    'temoignages' => TemoignageBulk::class,
    'categories' => CategoryBulk::class,
    'materiels' => MaterielBulk::class,
    'logiciels' => LogicielBulk::class,
    'realisations' => RealisationBulk::class,
    'abonnes' => AbonneBulk::class,
    'clients' => ClientBulk::class,
];
