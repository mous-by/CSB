<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Tâches planifiées (cron du serveur : `* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1`).
// Sauvegarde quotidienne puis vérification de l'archive (cahier §10, §12, REC-11).
Schedule::command('backup:run')->dailyAt(config('backup.time'))->withoutOverlapping()
    ->onSuccess(fn () => Artisan::call('backup:verify'));

// File d'attente (e-mails aux abonnés, notifications) : traitée chaque minute par le cron, sans démon permanent.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
