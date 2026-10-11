<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket de support d'un client (App\Services\Tickets\TicketService). `awaiting_team` : la dernière parole est au
 * client, l'équipe doit répondre (pastille du menu).
 */
#[Fillable(['number', 'client_id', 'subject', 'category', 'priority', 'status', 'device', 'assigned_to', 'last_activity_at', 'awaiting_team', 'closed_at'])]
class Ticket extends Model
{
    use LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class, 'priority' => TicketPriority::class, 'status' => TicketStatus::class,
            'last_activity_at' => 'datetime', 'closed_at' => 'datetime', 'awaiting_team' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest('id');
    }

    /** Fil visible par le client (sans les notes internes). */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TicketStatus::Resolu->value, TicketStatus::Ferme->value]);
    }

    public function activityNoun(): string
    {
        return 'Ticket';
    }

    public function activityLabel(): string
    {
        return $this->number.' — '.$this->subject;
    }
}
