<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Tâches planifiées (cron du serveur : `* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1`).
// Sauvegarde quotidienne puis vérification de l'archive (cahier §10, §12, REC-11).
Schedule::command('backup:run')->dailyAt(config('backup.time'))->withoutOverlapping()
    ->onSuccess(fn () => Artisan::call('backup:verify'));

// File d'attente (e-mails aux abonnés, notifications) : traitée chaque minute par le cron, sans démon permanent.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
// Conversions des vidéos de témoignages : file à part, une conversion peut durer plusieurs minutes.
Schedule::command('queue:work --queue=videos --stop-when-empty --timeout=1900 --tries=1')->everyMinute()->withoutOverlapping(60);
// Envois de vidéos abandonnés (jamais rattachés à un témoignage) : supprimés après 24 h.
Schedule::command('videos:clean')->daily();
// Statistiques de fréquentation : pages vues de plus de 13 mois supprimées.
Schedule::command('stats:purge')->daily();
