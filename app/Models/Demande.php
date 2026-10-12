<?php

namespace App\Models;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Enums\NeedType;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DemandeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Demande reçue par le site (cahier §6) : créée par App\Services\Demandes\DemandeService, traitée dans l'administration
 * (statut, notes, attribution). Les coordonnées du visiteur ne sont jamais affichées sur le site.
 */
#[Fillable([
    'type', 'status', 'name', 'organization', 'email', 'phone', 'subject', 'message', 'need_type', 'budget', 'deadline',
    'availability', 'subject_type', 'subject_id', 'subject_label', 'source_url', 'attachment_path', 'attachment_name',
    'attachment_size', 'consent_at', 'ip_address', 'user_agent', 'assigned_to', 'client_id', 'partenaire_id', 'closed_reason', 'status_changed_at',
    'notified_at', 'notification_error',
])]
class Demande extends Model
{
    /** @use HasFactory<DemandeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => DemandeType::class,
            'status' => DemandeStatus::class,
            'need_type' => NeedType::class,
            'consent_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'notified_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function subjectModel(): MorphTo
    {
        return $this->morphTo('subject');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DemandeNote::class)->latest('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [DemandeStatus::Nouvelle->value, DemandeStatus::EnCours->value, DemandeStatus::DevisEnvoye->value]);
    }

    /** Numéro lisible : D-2026-0042. */
    public function reference(): string
    {
        return 'D-'.$this->created_at?->format('Y').'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Moyens de réponse renseignés. */
    public function contactLine(): string
    {
        return collect([$this->email, $this->phone])->filter()->implode(' · ');
    }

    /** Champs jamais repris dans l'audit (bruit technique). */
    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'user_agent', 'notified_at', 'notification_error'];
    }

    public function activityNoun(): string
    {
        return 'Demande';
    }

    public function activityLabel(): string
    {
        return $this->reference().' — '.$this->name;
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class)->withTrashed();
    }
}
