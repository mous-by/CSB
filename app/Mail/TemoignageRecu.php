<?php

namespace App\Mail;

use App\Models\Temoignage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Témoignage envoyé par un client depuis le site : à modérer dans l'administration. */
class TemoignageRecu extends Mailable
{
    public function __construct(public readonly Temoignage $temoignage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouveau témoignage '.($this->temoignage->video_path ? 'en vidéo ' : '').'de '.$this->temoignage->author_name);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.temoignage', with: ['temoignage' => $this->temoignage]);
    }
}
