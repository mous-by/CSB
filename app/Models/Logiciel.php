<?php

namespace App\Models;

use App\Enums\LogicielStatus;
use App\Enums\Platform;
use App\Models\Concerns\HasGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Publishable;
use Database\Factories\LogicielFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Fiche d'un logiciel du catalogue (cahier §5.2) : enregistrée par App\Services\Contenus\LogicielService.
 * Un logiciel archivé sort du catalogue mais sa fiche reste accessible par son adresse (D-6).
 */
#[Fillable([
    'name', 'tagline', 'slug', 'summary', 'problem', 'description', 'category_id', 'features', 'platforms', 'status', 'price_text',
    'video_url', 'logo_media_id', 'is_published', 'is_featured', 'sort_order', 'seo_title', 'seo_description', 'seo_media_id',
])]
class Logiciel extends Model
{
    /** @use HasFactory<LogicielFactory> */
    use HasFactory, HasGallery, HasSlug, LogsActivity, Publishable, SoftDeletes;

    protected static function booted(): void
    {
        // Le sous-menu « Nos solutions » de l'en-tête est mis en cache (AppServiceProvider).
        static::saved(fn () => Cache::forget('site.nav_solutions.v2'));
        static::deleted(fn () => Cache::forget('site.nav_solutions.v2'));
    }

    protected function casts(): array
    {
        return [
            'status' => LogicielStatus::class,
            'features' => 'array',
            'platforms' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function publicPathPrefix(): ?string
    {
        return '/logiciels';
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /** Catégories secondaires (cahier §5.2). */
    public function secondaryCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'categorie_logiciel')->withTrashed();
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function seoImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'seo_media_id');
    }

    /** Catalogue public : publiés et non archivés. */
    public function scopeInCatalogue(Builder $query): Builder
    {
        return $query->published()->where('status', '!=', LogicielStatus::Archive->value);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name');
    }

    /** Toutes les catégories (principale puis secondaires), pour les filtres. */
    public function inCategory(int $categoryId): bool
    {
        return $this->category_id === $categoryId || $this->secondaryCategories->contains('id', $categoryId);
    }

    /** @return list<Platform> */
    public function platformList(): array
    {
        return array_values(array_filter(array_map(fn ($p) => Platform::tryFrom((string) $p), $this->platforms ?? [])));
    }

    /** @return list<string> */
    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', $this->features ?? [])));
    }

    /** Image de partage (cahier §5.2 « image de partage ») : choisie, sinon première capture, sinon logo. */
    public function shareImage(): ?Media
    {
        return $this->seoImage ?? $this->gallery->first() ?? $this->logo;
    }

    public function activityNoun(): string
    {
        return 'Logiciel';
    }

    public function activityLabel(): string
    {
        return (string) $this->name;
    }
}
