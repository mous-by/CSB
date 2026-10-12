<?php

namespace App\Console\Commands;

use App\Services\Videos\ChunkedVideoUpload;
use Illuminate\Console\Command;

/** Supprime les vidéos envoyées mais jamais rattachées à un témoignage (formulaire abandonné). */
class CleanVideoUploads extends Command
{
    protected $signature = 'videos:clean {--hours=24 : Âge minimal des envois à supprimer}';

    protected $description = 'Supprime les envois de vidéos abandonnés';

    public function handle(ChunkedVideoUpload $uploads): int
    {
        $count = $uploads->clean((int) $this->option('hours'));
        $this->info("{$count} envoi(s) abandonné(s) supprimé(s).");

        return self::SUCCESS;
    }
}
