<?php

namespace App\Models;

use App\Enums\CommissionKind;
use App\Enums\CommissionStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Partenaire de diffusion (App\Services\Partenaires) : lien personnel `?ref={ref_code}`, code promo facultatif, taux de
 * commission par type de vente (réglables), compte de l'administration facultatif (rôle Communication).
 */
#[Fillable([
    'user_id', 'name', 'ref_code', 'promo_code', 'promo_discount', 'promo_ends_on', 'rate_materiel', 'rate_logiciel',
    'rate_sur_mesure', 'cookie_days', 'phone', 'email', 'payment_info', 'notes', 'is_active',
])]
class Partenaire extends Model
{
    use LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'promo_discount' => 'float', 'promo_ends_on' => 'date', 'rate_materiel' => 'float', 'rate_logiciel' => 'float',
            'rate_sur_mesure' => 'float', 'cookie_days' => 'integer', 'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class)->latest('id');
    }

    public function visites(): HasMany
    {
        return $this->hasMany(PartenaireVisite::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function rateFor(CommissionKind $kind): float
    {
        return (float) $this->{$kind->rateColumn()};
    }

    /** Code promo utilisable aujourd'hui (actif, avec une remise, date de fin non dépassée). */
    public function promoActive(): bool
    {
        return $this->is_active && $this->promo_code && $this->promo_discount > 0
            && ($this->promo_ends_on === null || ! $this->promo_ends_on->isBefore(today()));
    }

    public function link(?string $url = null): string
    {
        $url ??= route('site.home');

        return $url.(str_contains($url, '?') ? '&' : '?').'ref='.$this->ref_code;
    }

    public function totalCommissions(CommissionStatus $status): int
    {
        return (int) $this->commissions()->where('status', $status->value)->sum('amount');
    }

    public function activityNoun(): string
    {
        return 'Partenaire';
    }

    public function activityLabel(): string
    {
        return $this->name;
    }
}
