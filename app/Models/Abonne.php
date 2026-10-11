<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Abonné aux actualités de CSB. Créé par le formulaire du pied de page (ou l'administration), désinscrit par le lien
 * de chaque e-mail (jeton) : il reste en base, marqué désinscrit.
 */
#[Fillable(['email', 'name', 'token', 'source', 'subscribed_at', 'unsubscribed_at', 'ip_address'])]
class Abonne extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['subscribed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Abonne $abonne) {
            $abonne->email = mb_strtolower(trim($abonne->email));
            $abonne->token ??= Str::random(48);
            $abonne->subscribed_at ??= now();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'token', 'ip_address'];
    }

    public function activityModule(): string
    {
        return 'abonnes';
    }

    public function activityNoun(): string
    {
        return 'Abonné';
    }

    public function activityLabel(): string
    {
        return (string) $this->email;
    }
}
