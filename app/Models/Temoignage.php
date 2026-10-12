<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\TemoignageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Témoignage d'un client (cahier §4 « témoignages autorisés ») : écrit et/ou vidéo (fichier hébergé par le site, ou lien
 * externe), photo et note de l'auteur. Publié seulement avec l'accord de son auteur ; créé masqué par défaut. Un
 * témoignage envoyé par un client (`source` = client) attend la modération de l'équipe.
 */
#[Fillable([
    'author_name', 'author_title', 'organization', 'photo_media_id', 'rating', 'content', 'video_path', 'video_poster_path',
    'video_duration', 'video_status', 'video_error', 'video_url', 'source', 'contact', 'client_id', 'consent_at',
    'sort_order', 'is_published', 'is_featured',
])]
class Temoignage extends Model
{
    /** @use HasFactory<TemoignageFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean', 'is_featured' => 'boolean', 'sort_order' => 'integer', 'rating' => 'integer',
            'video_duration' => 'integer', 'consent_at' => 'datetime',
        ];
    }

    public const VIDEO_PROCESSING = 'processing';

    public const VIDEO_READY = 'ready';

    public const VIDEO_FAILED = 'failed';

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /** Vidéo hébergée par le site et prête à être lue. */
    public function hasVideo(): bool
    {
        return $this->video_path !== null && $this->video_status === self::VIDEO_READY;
    }

    /** Vidéo affichable : fichier prêt ou lien externe. */
    public function isVideo(): bool
    {
        return $this->hasVideo() || $this->video_url !== null;
    }

    public function videoUrl(): ?string
    {
        return $this->hasVideo() ? Storage::disk('public')->url($this->video_path) : null;
    }

    public function posterUrl(): ?string
    {
        return $this->video_poster_path ? Storage::disk('public')->url($this->video_poster_path) : $this->photo?->url();
    }

    /** « 1:24 » */
    public function durationForDisplay(): ?string
    {
        return $this->video_duration ? intdiv($this->video_duration, 60).':'.str_pad((string) ($this->video_duration % 60), 2, '0', STR_PAD_LEFT) : null;
    }

    /** Nom du site d'un lien externe (« YouTube », « Facebook »…). */
    public function videoPlatform(): ?string
    {
        if (! $this->video_url) {
            return null;
        }
        $host = (string) parse_url($this->video_url, PHP_URL_HOST);

        return match (true) {
            str_contains($host, 'youtu') => 'YouTube',
            str_contains($host, 'facebook') || str_contains($host, 'fb.watch') => 'Facebook',
            str_contains($host, 'tiktok') => 'TikTok',
            str_contains($host, 'instagram') => 'Instagram',
            default => 'la vidéo',
        };
    }

    /** Envoyé par un client depuis le site et pas encore publié. */
    public function awaitingModeration(): bool
    {
        return $this->source === 'client' && ! $this->is_published;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where(fn ($q) => $q->whereNotNull('video_path')->where('video_status', self::VIDEO_READY))->orWhereNotNull('video_url'));
    }

    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'video_error', 'video_status', 'video_duration'];
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
