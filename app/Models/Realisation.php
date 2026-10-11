<?php

namespace App\Models;

use App\Models\Concerns\HasGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Publishable;
use Database\Factories\RealisationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Projet réalisé par CSB, publié seulement avec l'autorisation du client (cahier §4) ; le nom du client est facultatif.
 */
#[Fillable([
    'title', 'slug', 'client_name', 'sector', 'year', 'summary', 'context', 'need', 'solution', 'technologies',
    'cover_media_id', 'logiciel_id', 'is_published', 'sort_order', 'seo_title', 'seo_description', 'seo_media_id',
])]
class Realisation extends Model
{
    /** @use HasFactory<RealisationFactory> */
    use HasFactory, HasGallery, HasSlug, LogsActivity, Publishable, SoftDeletes;

    protected function casts(): array
    {
        return ['technologies' => 'array', 'is_published' => 'boolean', 'published_at' => 'datetime', 'year' => 'integer', 'sort_order' => 'integer'];
    }

    public function publicPathPrefix(): ?string
    {
        return '/realisations';
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function seoImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'seo_media_id');
    }

    /** Logiciel CSB utilisé dans le projet (lien vers sa fiche). */
    public function logiciel(): BelongsTo
    {
        return $this->belongsTo(Logiciel::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('year')->orderByDesc('id');
    }

    /** @return list<string> */
    public function technologyList(): array
    {
        return array_values(array_filter(array_map('trim', $this->technologies ?? [])));
    }

    public function images(): Collection
    {
        return collect([$this->cover])->merge($this->gallery)->filter()->unique('id')->values();
    }

    public function shareImage(): ?Media
    {
        return $this->seoImage ?? $this->cover ?? $this->gallery->first();
    }

    public function activityNoun(): string
    {
        return 'Réalisation';
    }

    public function activityLabel(): string
    {
        return (string) $this->title;
    }
}
