<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\Statistiques\SiteStats;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Supprime les pages vues de plus de 13 mois (les statistiques remontent au plus à 12 mois). Les visites supprimées
 * sont ajoutées au compteur archivé, pour que le nombre de visites affiché sur le site ne baisse pas.
 */
class PurgeStats extends Command
{
    protected $signature = 'stats:purge';

    protected $description = 'Supprime les pages vues de plus de 13 mois';

    public function handle(): int
    {
        $old = DB::table('page_vues')->where('day', '<', today()->subMonths(13)->toDateString());
        $visits = (int) (clone $old)->selectRaw('COUNT(DISTINCT day, visitor) as total')->value('total');

        $deleted = DB::transaction(function () use ($old, $visits) {
            if ($visits > 0) {
                $archive = AppSetting::firstOrNew(['key' => SiteStats::ARCHIVED_VISITS_KEY]);
                $archive->value = (string) ((int) $archive->value + $visits);
                $archive->save();
            }

            return $old->delete();
        });
        $this->info("{$deleted} page(s) vue(s) supprimée(s).");

        return self::SUCCESS;
    }
}
