<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppAlert;
use App\Support\PhoneNumber;
use App\Support\SiteSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Alertes WhatsApp à l'équipe (cahier §15.3) : nouvelle demande, nouvelle commande, nouveau ticket. Envoi par l'API
 * officielle WhatsApp Business (Cloud API de Meta) avec un modèle de message approuvé, en file d'attente : le visiteur
 * n'attend jamais WhatsApp, et un échec n'empêche rien (il est journalisé par SendWhatsAppAlert).
 */
class WhatsAppNotifier
{
    public function __construct(private readonly SiteSettings $settings) {}

    public function configured(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    /** Destinataires (Paramètres → WhatsApp), au format international sans « + ». @return list<string> */
    public function recipients(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($line) => ($n = PhoneNumber::normalize($line)) ? ltrim($n, '+') : null,
            $this->settings->lines('whatsapp.recipients'),
        ))));
    }

    /** Événement activé dans les paramètres (demandes, commandes, tickets). */
    public function enabled(string $event): bool
    {
        return in_array($event, array_map('mb_strtolower', $this->settings->lines('whatsapp.events')), true);
    }

    /** Met une alerte en file d'attente pour chaque destinataire (rien si non configuré ou événement désactivé). */
    public function alert(string $event, string $text): int
    {
        if (! $this->configured() || ! $this->enabled($event)) {
            return 0;
        }
        $recipients = $this->recipients();
        foreach ($recipients as $to) {
            SendWhatsAppAlert::dispatch($to, $text, $event);
        }

        return count($recipients);
    }

    /** Envoi immédiat d'un message modèle à un numéro (appelé par la tâche de la file d'attente et par le test). */
    public function send(string $to, string $text): Response
    {
        $config = config('services.whatsapp');

        return Http::withToken((string) $config['token'])->acceptJson()->timeout(15)
            ->post(rtrim((string) $config['base_url'], '/')."/{$config['api_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $config['template'],
                    'language' => ['code' => $config['language']],
                    'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => mb_substr($text, 0, 1000)]]]],
                ],
            ]);
    }
}
