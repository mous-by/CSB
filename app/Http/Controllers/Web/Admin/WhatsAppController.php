<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Audit\ActivityLogger;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\PhoneNumber;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/** Alertes WhatsApp : état de la configuration (.env), numéros à prévenir, événements, message de test, échecs récents. */
class WhatsAppController extends Controller
{
    public const EVENTS = ['demandes' => 'Nouvelle demande (contact, devis, démonstration, disponibilité)', 'commandes' => 'Nouvelle commande de la boutique', 'tickets' => 'Nouveau ticket d\'assistance'];

    public function edit(WhatsAppNotifier $notifier, SiteSettings $settings): View
    {
        return view('whatsapp.settings', [
            'configured' => $notifier->configured(),
            'recipients' => $settings->get('whatsapp.recipients'),
            'events' => array_map('mb_strtolower', $settings->lines('whatsapp.events')),
            'failures' => ActivityLog::where('action', 'whatsapp.failed')->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, SiteSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                foreach (array_filter(array_map('trim', preg_split('/\R/', (string) $value))) as $line) {
                    if (! PhoneNumber::normalize($line)) {
                        $fail("Numéro invalide : « {$line} ».");
                    }
                }
            }],
            'events' => ['nullable', 'array'],
            'events.*' => ['in:'.implode(',', array_keys(self::EVENTS))],
        ], [], ['recipients' => 'numéros']);

        $settings->save([
            'whatsapp.recipients' => trim((string) ($data['recipients'] ?? '')),
            'whatsapp.events' => implode("\n", $data['events'] ?? []),
        ]);

        return redirect()->route('whatsapp.settings')->with('status', 'Alertes WhatsApp enregistrées.');
    }

    /** Envoie tout de suite un message de test au premier numéro (sans file d'attente, pour voir l'erreur éventuelle). */
    public function test(Request $request, WhatsAppNotifier $notifier, ActivityLogger $logger): RedirectResponse
    {
        if (! $notifier->configured()) {
            return back()->with('error', 'WhatsApp n\'est pas configuré : WHATSAPP_TOKEN et WHATSAPP_PHONE_NUMBER_ID sont vides dans .env.');
        }
        $to = $notifier->recipients()[0] ?? null;
        if (! $to) {
            return back()->with('error', 'Ajoutez d\'abord un numéro à prévenir.');
        }

        try {
            $response = $notifier->send($to, 'Message de test des alertes CSB envoyé par '.$request->user()->name.'.');
            $response->throw();
        } catch (Throwable $e) {
            $logger->log('whatsapp.failed', 'whatsapp', 'Message de test WhatsApp non envoyé', new: ['error' => mb_substr($e->getMessage(), 0, 300)]);

            return back()->with('error', 'Échec de l\'envoi : '.mb_substr($e->getMessage(), 0, 300));
        }
        $logger->log('whatsapp.tested', 'whatsapp', 'Message de test WhatsApp envoyé');

        return back()->with('status', 'Message de test envoyé : vérifiez le téléphone.');
    }
}
