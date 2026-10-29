<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Accusé de réception d'un nouveau ticket ($opened), réponse de l'équipe ou changement de statut, pour le demandeur : lien vers l'espace client, ou, pour un ticket ouvert
 * depuis une application connectée, invitation à répondre depuis cette application (pas de compte CSB).
 */
class TicketForClient extends Mailable
{
    public function __construct(public readonly Ticket $ticket, public readonly ?TicketMessage $reply, public readonly bool $opened = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->opened
            ? "Demande d'assistance {$this->ticket->number} bien reçue"
            : "Votre demande d'assistance {$this->ticket->number} — ".$this->ticket->status->label());
    }

    public function content(): Content
    {
        return new Content(view: 'mail.ticket', with: [
            'ticket' => $this->ticket, 'ticketMessage' => $this->reply,
            'intro' => match (true) {
                $this->opened => "Bonjour {$this->ticket->requesterName()}, nous avons bien reçu votre demande d'assistance {$this->ticket->number}. Un technicien CSB l'étudie et vous répond dès que possible ; vous serez prévenu par e-mail à chaque réponse.",
                $this->reply !== null => 'L\'équipe CSB vous a répondu.',
                default => 'Le statut de votre demande a changé : '.$this->ticket->status->label().'.',
            },
            'url' => $this->ticket->isExternal() ? null : route('client.tickets.show', $this->ticket), 'button' => $this->opened ? 'Suivre ma demande' : 'Voir et répondre',
            'outro' => $this->ticket->isExternal() ? "Pour répondre, ouvrez « Assistance CSB » dans {$this->ticket->source_app}." : null,
        ]);
    }
}
