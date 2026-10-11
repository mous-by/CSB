<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Publishable;
use App\Support\Markdown;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Article d'actualité ou de conseil (cahier §4). Visible sur le site s'il est publié et que sa date de publication est
 * passée (une date future le programme).
 */
#[Fillable(['title', 'slug', 'excerpt', 'body', 'category_id', 'cover_media_id', 'author_id', 'is_published', 'published_at', 'seo_title', 'seo_description', 'seo_media_id'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasSlug, LogsActivity, Publishable, SoftDeletes;

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime', 'newsletter_sent_at' => 'datetime'];
    }

    public function publicPathPrefix(): ?string
    {
        return '/actualites';
    }

    public function slugSource(): string
    {
        return 'title';
    }

    /** Publié et date atteinte. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function seoImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'seo_media_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    public function isVisible(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->is_published && $this->published_at?->isFuture();
    }

    /** Extrait saisi, sinon début du texte. */
    public function excerptText(int $limit = 200): string
    {
        return $this->excerpt ?: Markdown::toText($this->body, $limit);
    }

    /** Temps de lecture estimé (200 mots par minute). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags(Markdown::toHtml($this->body))) / 200));
    }

    public function shareImage(): ?Media
    {
        return $this->seoImage ?? $this->cover;
    }

    public function activityNoun(): string
    {
        return 'Article';
    }

    public function activityLabel(): string
    {
        return (string) $this->title;
    }
}
