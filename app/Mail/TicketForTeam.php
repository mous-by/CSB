<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Nouveau ticket ou nouvelle réponse d'un demandeur (client ou utilisateur d'une application connectée), pour l'équipe. */
class TicketForTeam extends Mailable
{
    public function __construct(public readonly Ticket $ticket, public readonly string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[{$this->ticket->number}] {$this->event} — {$this->ticket->subject}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.ticket', with: ['ticket' => $this->ticket, 'ticketMessage' => $this->ticket->messages->last(), 'intro' => $this->event.' de '.$this->ticket->requesterName().($this->ticket->isExternal() ? " (via {$this->ticket->source_app})" : '').'.', 'url' => route('tickets.show', $this->ticket), 'button' => 'Traiter le ticket']);
    }
}
