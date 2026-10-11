<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\TemoignageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Témoignage d'un client (accueil, cahier §4 « témoignages autorisés ») : publié seulement avec l'accord de son auteur ;
 * créé masqué par défaut.
 */
#[Fillable(['author_name', 'author_title', 'organization', 'content', 'sort_order', 'is_published'])]
class Temoignage extends Model
{
    /** @use HasFactory<TemoignageFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function activityNoun(): string
    {
        return 'Témoignage';
    }

    public function activityLabel(): string
    {
        return (string) $this->author_name;
    }
}
