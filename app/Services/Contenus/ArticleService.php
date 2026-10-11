<?php

namespace App\Services\Contenus;

use App\Models\Article;
use App\Models\User;
use App\Services\Medias\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'un article (cahier §4) : auteur (première saisie), couverture, date de publication, publication
 * (`articles.publish`, journalisée). Sans droit de publication, l'état et la date restent tels quels.
 */
class ArticleService
{
    public function __construct(private readonly MediaService $medias, private readonly ContentPublisher $publisher) {}

    /**
     * @param  array<string, mixed>  $data  données validées (ArticleRequest)
     */
    public function save(?Article $article, array $data, User $user, ?UploadedFile $coverUpload = null): Article
    {
        if ($coverUpload) {
            $data['cover_media_id'] = $this->medias->store($coverUpload, $user, $data['title'], 'Actualités')->id;
        }
        $data['slug'] = $data['slug'] ?? $article?->slug;

        if (! $user->can('articles.publish')) {
            $data['is_published'] = $article?->is_published ?? false;
            $data['published_at'] = $article?->published_at;
        }

        return DB::transaction(function () use ($article, $data, $user) {
            $wasPublished = (bool) $article?->is_published;

            $article ??= new Article(['author_id' => $user->id]);
            $article->fill(collect($data)->except(['cover_upload'])->all())->save();
            $this->publisher->logChange($article, 'articles', $wasPublished);

            return $article->fresh(['category', 'cover', 'author']);
        });
    }
}
