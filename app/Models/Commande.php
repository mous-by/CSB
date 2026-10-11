<?php

namespace App\Models;

use App\Enums\CommandeStatus;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Commande de la boutique (App\Services\Boutique\CommandeService). */
#[Fillable([
    'number', 'client_id', 'status', 'name', 'organization', 'email', 'phone', 'delivery_method', 'address', 'city',
    'payment_method', 'payment_reference', 'total', 'notes', 'cancel_reason', 'handled_by', 'confirmed_at', 'paid_at',
    'delivered_at', 'ip_address', 'access_token',
])]
class Commande extends Model
{
    use LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => CommandeStatus::class, 'delivery_method' => DeliveryMethod::class, 'payment_method' => PaymentMethod::class,
            'total' => 'integer', 'confirmed_at' => 'datetime', 'paid_at' => 'datetime', 'delivered_at' => 'datetime',
        ];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class)->oldest('id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [CommandeStatus::Livree->value, CommandeStatus::Annulee->value]);
    }

    public function totalForDisplay(): string
    {
        return Materiel::money($this->total);
    }

    public function itemCount(): int
    {
        return (int) $this->lignes->sum('quantity');
    }

    public function activityNoun(): string
    {
        return 'Commande';
    }

    public function activityLabel(): string
    {
        return $this->number.' — '.$this->name;
    }

    /** Le jeton d'accès à la page de suivi n'apparaît jamais dans l'audit. */
    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'access_token', 'ip_address'];
    }
}
