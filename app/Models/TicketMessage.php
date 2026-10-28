<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message du fil d'un ticket : du demandeur (client de l'espace client, ou utilisateur d'une application connectée :
 * `from_reporter`), ou de l'équipe (public, ou note interne invisible du demandeur).
 */
#[Fillable(['ticket_id', 'client_id', 'user_id', 'from_reporter', 'body', 'is_internal', 'attachment_path', 'attachment_name', 'attachment_size'])]
class TicketMessage extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['is_internal' => 'boolean', 'from_reporter' => 'boolean', 'attachment_size' => 'integer'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class)->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function fromClient(): bool
    {
        return $this->client_id !== null || $this->from_reporter;
    }

    public function activityModule(): string
    {
        return 'tickets';
    }

    public function activityNoun(): string
    {
        return $this->is_internal ? 'Note interne de ticket' : 'Message de ticket';
    }

    public function activityLabel(): string
    {
        return 'Ticket #'.$this->ticket_id;
    }
}
