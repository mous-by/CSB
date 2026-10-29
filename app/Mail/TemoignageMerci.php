<?php

namespace App\Mail;

use App\Models\Temoignage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Remerciement à l'auteur d'un témoignage envoyé depuis le site (contact = e-mail) : publié après relecture. */
class TemoignageMerci extends Mailable
{
    public function __construct(public readonly Temoignage $temoignage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Merci pour votre témoignage');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.temoignage-merci', with: ['temoignage' => $this->temoignage]);
    }
}
