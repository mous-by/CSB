<?php

namespace App\Console\Commands;

use App\Services\Push\WebPush;
use Illuminate\Console\Command;

/** Crée une paire de clés VAPID pour les notifications push, à copier dans `.env` (une seule fois par site). */
class PushKeys extends Command
{
    protected $signature = 'push:keys';

    protected $description = 'Génère les clés VAPID des notifications push (à copier dans .env)';

    public function handle(): int
    {
        $keys = WebPush::generateKeys();

        $this->info('Copiez ces lignes dans .env (ne les changez plus ensuite : les appareils abonnés devraient se réabonner) :');
        $this->line('VAPID_PUBLIC_KEY='.$keys['public']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['private']);
        $this->line('VAPID_SUBJECT=mailto:contact@votre-domaine');

        return self::SUCCESS;
    }
}
