<?php

namespace App\Mail;

use App\Models\Commande;
use App\Services\Boutique\CommandeService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Récapitulatif de commande et changements de statut, pour le client (lien vers sa page de suivi). */
class CommandeForClient extends Mailable
{
    public function __construct(public readonly Commande $commande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre commande {$this->commande->number} — {$this->commande->status->label()}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.commande', with: [
            'commande' => $this->commande, 'intro' => $this->commande->status->meaning(),
            'url' => route('site.commande.suivi', [$this->commande->number, $this->commande->access_token]), 'button' => 'Suivre ma commande',
            'instructions' => $this->commande->status->isOpen() ? app(CommandeService::class)->paymentInstructions($this->commande) : null,
        ]);
    }
}
