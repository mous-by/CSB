<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nouvelle commande, pour l'équipe (lien vers l'administration). */
class CommandeForTeam extends Mailable
{
    public function __construct(public readonly Commande $commande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nouvelle commande {$this->commande->number} — {$this->commande->totalForDisplay()}",
            replyTo: $this->commande->email ? [new Address($this->commande->email, $this->commande->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.commande', with: [
            'commande' => $this->commande, 'intro' => "Nouvelle commande de {$this->commande->name} ({$this->commande->phone}) : rappelez le client pour confirmer.",
            'url' => route('commandes.show', $this->commande), 'button' => 'Traiter la commande', 'instructions' => null,
        ]);
    }
}
