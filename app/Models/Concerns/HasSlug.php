<?php

namespace App\Models\Concerns;

use App\Models\Redirection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Adresse lisible et unique d'un contenu public (cahier §11.1 « URLs lisibles et stables ») : générée depuis le titre à
 * la création si elle n'est pas saisie, normalisée (minuscules, tirets), rendue unique parmi les contenus du même type
 * (y compris supprimés, pour ne jamais réattribuer une ancienne adresse). `slugScope()` restreint l'unicité (ex. type
 * de catégorie). Les routes publiques lient par l'adresse (`{logiciel:slug}`), l'administration par l'identifiant.
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        // Adresse publique modifiée : l'ancienne redirige vers la nouvelle (cahier §11.1).
        static::updated(function (Model $model) {
            $prefix = $model->publicPathPrefix();
            if ($prefix !== null && $model->wasChanged('slug') && $model->getOriginal('slug')) {
                Redirection::record($prefix.'/'.$model->getOriginal('slug'), $prefix.'/'.$model->slug);
            }
        });

        static::saving(function (Model $model) {
            if ($model->exists && ! $model->isDirty('slug')) {
                return;
            }

            $base = Str::slug((string) ($model->slug ?: $model->getAttribute($model->slugSource())), '-', 'fr') ?: 'element';
            $model->slug = $model->uniqueSlug(Str::limit($base, 180, ''));
        });
    }

    /** Champ dont l'adresse est tirée (titre, nom, désignation). */
    abstract public function slugSource(): string;

    /** Préfixe de l'adresse publique (« /logiciels »), ou null si le contenu n'a pas de page propre. */
    public function publicPathPrefix(): ?string
    {
        return null;
    }

    /** Restriction de l'unicité (par défaut : toute la table). */
    protected function slugScope(Builder $query): Builder
    {
        return $query;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $n = 2;

        while ($this->slugTaken($slug)) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    private function slugTaken(string $slug): bool
    {
        $query = static::query();
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        return $this->slugScope($query)->where('slug', $slug)->whereKeyNot($this->getKey())->exists();
    }
}
