<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jeton d'accès à l'API de synchronisation du stock. Seule son empreinte SHA-256 est enregistrée : le jeton en clair
 * n'est montré qu'une fois, à sa création.
 */
#[Fillable(['name', 'token_hash', 'last_four', 'last_used_at', 'created_by', 'revoked_at'])]
#[Hidden(['token_hash'])]
class ApiToken extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** Crée un jeton ; renvoie [modèle, jeton en clair]. @return array{0: self, 1: string} */
    public static function issue(string $name, ?User $creator): array
    {
        $plain = 'csb_'.Str::random(44);

        return [self::create(['name' => $name, 'token_hash' => hash('sha256', $plain), 'last_four' => substr($plain, -4), 'created_by' => $creator?->id]), $plain];
    }

    public static function findActive(string $plain): ?self
    {
        return $plain === '' ? null : self::whereNull('revoked_at')->where('token_hash', hash('sha256', $plain))->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function activityModule(): string
    {
        return 'synchro';
    }

    public function activityNoun(): string
    {
        return 'Jeton d\'API';
    }

    public function activityLabel(): string
    {
        return $this->name.' (…'.$this->last_four.')';
    }

    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'token_hash', 'last_used_at'];
    }
}
