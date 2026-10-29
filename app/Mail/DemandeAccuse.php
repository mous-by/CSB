<?php

namespace App\Mail;

use App\Enums\DemandeType;
use App\Models\Demande;
use App\Support\SiteSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Accusé de réception au visiteur qui a laissé son e-mail (contact, devis, démonstration, prix / disponibilité) :
 * référence, récapitulatif, moyens de joindre CSB. « Répondre » écrit à l'équipe.
 */
class DemandeAccuse extends Mailable
{
    public function __construct(public readonly Demande $demande) {}

    public function envelope(): Envelope
    {
        $team = app(SiteSettings::class)->notificationEmail();

        return new Envelope(
            subject: 'Nous avons bien reçu votre demande — '.$this->demande->reference(),
            replyTo: $team ? [$team] : [],
        );
    }

    public function content(): Content
    {
        $what = match ($this->demande->type) {
            DemandeType::Contact => 'votre message',
            DemandeType::Devis => 'votre demande de devis',
            DemandeType::Demonstration => 'votre demande de démonstration',
            DemandeType::Disponibilite => 'votre demande de prix et de disponibilité',
        };

        return new Content(view: 'mail.demande-accuse', with: [
            'demande' => $this->demande,
            'what' => $what,
            'url' => $this->demande->client_id ? route('client.demandes') : null,
        ]);
    }
}
