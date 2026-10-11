<?php

namespace App\Services\Contenus;

use App\Models\Materiel;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Medias\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'une fiche matériel (cahier §5.4) : caractéristiques, prix, état, disponibilité, photos (nouvelles
 * images enregistrées dans la bibliothèque), publication (`materiels.publish`, journalisée).
 */
class MaterielService
{
    public function __construct(private readonly MediaService $medias, private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $data  données validées (MaterielRequest)
     * @param  list<UploadedFile>  $galleryUploads
     */
    public function save(?Materiel $materiel, array $data, User $user, ?UploadedFile $mainUpload = null, array $galleryUploads = []): Materiel
    {
        if ($mainUpload) {
            $data['main_media_id'] = $this->medias->store($mainUpload, $user, $data['designation'], 'Matériel')->id;
        }
        $newGallery = $this->medias->storeMany($galleryUploads, $user, 'Matériel', $data['designation']);

        $data['specs'] = array_map(fn ($row) => [$row['label'], $row['value']], $data['specs'] ?? []);
        $data['slug'] = $data['slug'] ?? $materiel?->slug;
        if (! $user->can('materiels.publish')) {
            $data['is_published'] = $materiel?->is_published ?? false;
        }

        return DB::transaction(function () use ($materiel, $data, $newGallery) {
            $wasPublished = (bool) $materiel?->is_published;

            $materiel ??= new Materiel;
            $materiel->fill(collect($data)->except(['gallery', 'main_upload', 'gallery_upload'])->all())->save();
            $this->medias->syncGallery($materiel, [...($data['gallery'] ?? []), ...$newGallery]);
            $this->logPublication($materiel, $wasPublished);

            return $materiel->fresh(['category', 'gallery', 'mainImage']);
        });
    }

    public function togglePublication(Materiel $materiel): Materiel
    {
        return DB::transaction(function () use ($materiel) {
            $was = $materiel->is_published;
            $materiel->update(['is_published' => ! $was]);
            $this->logPublication($materiel, $was);

            return $materiel;
        });
    }

    private function logPublication(Materiel $materiel, bool $wasPublished): void
    {
        if ($materiel->is_published !== $wasPublished) {
            $action = $materiel->is_published ? 'published' : 'unpublished';
            $this->logger->log("materiels.{$action}", 'materiels', ($materiel->is_published ? 'Publication' : 'Dépublication')." — Matériel « {$materiel->designation} »", $materiel);
        }
    }
}
