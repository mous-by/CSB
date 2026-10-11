<?php

namespace App\Mail;

use App\Models\Demande;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notification d'une nouvelle demande à l'équipe de CSB (cahier §3.1, §6.4). « Répondre » écrit directement au visiteur
 * s'il a laissé son e-mail. La pièce jointe n'est pas transmise : elle se télécharge depuis l'administration.
 */
class NouvelleDemande extends Mailable
{
    public function __construct(public readonly Demande $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] '.$this->demande->type->label().' — '.$this->demande->name,
            replyTo: $this->demande->email ? [new Address($this->demande->email, $this->demande->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.nouvelle-demande', with: ['demande' => $this->demande]);
    }
}
