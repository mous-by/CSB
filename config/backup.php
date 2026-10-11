<?php

/*
|--------------------------------------------------------------------------
| Sauvegarde de la base (cahier §10, §12, REC-11, CLAUDE.md §9)
|--------------------------------------------------------------------------
|
| `php artisan backup:run` : export compressé de la base dans `path`, `keep` versions conservées, copie séparée
| dans `copy_path` si renseigné (autre disque, partage réseau monté…). Planifiée chaque jour (routes/console.php).
| `php artisan backup:verify` : contrôle de la dernière sauvegarde (archive lisible, tables présentes).
|
*/

return [

    // Chemin de mysqldump (XAMPP : /opt/lampp/bin/mysqldump).
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    'copy_path' => env('BACKUP_COPY_PATH'),

    'keep' => (int) env('BACKUP_KEEP', 30),

    'time' => env('BACKUP_TIME', '02:00'),

    // Tables dont `backup:verify` exige la présence dans le dump : chaque module y ajoute ses tables essentielles.
    'required_tables' => ['users', 'audit_logs', 'app_settings', 'demandes', 'logiciels', 'materiels', 'medias'],

];
