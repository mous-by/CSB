<?php

namespace App\Services\Contenus;

use App\Models\Realisation;
use App\Models\User;
use App\Services\Medias\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'une réalisation (cahier §4) : visuels (nouvelles images enregistrées dans la bibliothèque),
 * technologies, publication (`realisations.publish`, journalisée).
 */
class RealisationService
{
    public function __construct(private readonly MediaService $medias, private readonly ContentPublisher $publisher) {}

    /**
     * @param  array<string, mixed>  $data  données validées (RealisationRequest)
     * @param  list<UploadedFile>  $galleryUploads
     */
    public function save(?Realisation $realisation, array $data, User $user, ?UploadedFile $coverUpload = null, array $galleryUploads = []): Realisation
    {
        if ($coverUpload) {
            $data['cover_media_id'] = $this->medias->store($coverUpload, $user, $data['title'], 'Réalisations')->id;
        }
        $newGallery = $this->medias->storeMany($galleryUploads, $user, 'Réalisations', $data['title']);

        $data['technologies'] = array_values($data['technologies'] ?? []);
        $data['slug'] = $data['slug'] ?? $realisation?->slug;
        if (! $user->can('realisations.publish')) {
            $data['is_published'] = $realisation?->is_published ?? false;
        }

        return DB::transaction(function () use ($realisation, $data, $newGallery) {
            $wasPublished = (bool) $realisation?->is_published;

            $realisation ??= new Realisation;
            $realisation->fill(collect($data)->except(['gallery', 'cover_upload', 'gallery_upload'])->all())->save();
            $this->medias->syncGallery($realisation, [...($data['gallery'] ?? []), ...$newGallery]);
            $this->publisher->logChange($realisation, 'realisations', $wasPublished);

            return $realisation->fresh(['cover', 'gallery', 'logiciel']);
        });
    }
}
