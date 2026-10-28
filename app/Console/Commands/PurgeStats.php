<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Supprime les pages vues de plus de 13 mois (les statistiques remontent au plus à 12 mois). */
class PurgeStats extends Command
{
    protected $signature = 'stats:purge';

    protected $description = 'Supprime les pages vues de plus de 13 mois';

    public function handle(): int
    {
        $deleted = DB::table('page_vues')->where('day', '<', today()->subMonths(13)->toDateString())->delete();
        $this->info("{$deleted} page(s) vue(s) supprimée(s).");

        return self::SUCCESS;
    }
}
