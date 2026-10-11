<?php

namespace App\Services\Contenus;

use App\Enums\LogicielStatus;
use App\Models\Logiciel;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Medias\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'une fiche logiciel (cahier §5.2) : champs, catégories secondaires, logo et captures (nouvelles images
 * enregistrées dans la bibliothèque), publication. Publier, dépublier et archiver exigent `logiciels.publish` et sont
 * journalisés explicitement.
 */
class LogicielService
{
    public function __construct(private readonly MediaService $medias, private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $data  données validées (LogicielRequest)
     * @param  list<UploadedFile>  $galleryUploads
     */
    public function save(?Logiciel $logiciel, array $data, User $user, ?UploadedFile $logoUpload = null, array $galleryUploads = []): Logiciel
    {
        $canPublish = $user->can('logiciels.publish');

        // Images envoyées : enregistrées d'abord dans la bibliothèque (un échec de lecture refuse tout l'envoi).
        if ($logoUpload) {
            $data['logo_media_id'] = $this->medias->store($logoUpload, $user, $data['name'], 'Logiciels')->id;
        }
        $newGallery = $this->medias->storeMany($galleryUploads, $user, 'Logiciels', 'Capture — '.$data['name']);

        $data['features'] = array_values($data['features'] ?? []);
        $data['platforms'] = array_values(array_unique($data['platforms'] ?? []));
        $data['slug'] = $data['slug'] ?? $logiciel?->slug;

        if (! $canPublish) {
            // Sans le droit de publier : la publication et l'archivage restent tels quels.
            $data['is_published'] = $logiciel?->is_published ?? false;
            // Archiver (ou désarchiver) relève aussi de la publication.
            $oldStatus = $logiciel?->status->value;
            $newStatus = $data['status'] ?? $oldStatus;
            if ($newStatus !== $oldStatus && in_array(LogicielStatus::Archive->value, [$newStatus, $oldStatus], true)) {
                $data['status'] = $oldStatus ?? LogicielStatus::Disponible->value;
            }
        }

        return DB::transaction(function () use ($logiciel, $data, $newGallery) {
            $wasPublished = (bool) $logiciel?->is_published;
            $oldStatus = $logiciel?->status;

            $logiciel ??= new Logiciel;
            $logiciel->fill(collect($data)->except(['secondary_categories', 'gallery', 'logo_upload', 'gallery_upload'])->all())->save();

            $logiciel->secondaryCategories()->sync(array_values(array_diff(array_map('intval', $data['secondary_categories'] ?? []), [(int) $logiciel->category_id])));
            $this->medias->syncGallery($logiciel, [...($data['gallery'] ?? []), ...$newGallery]);

            $this->logPublication($logiciel, $wasPublished, $oldStatus);

            return $logiciel->fresh(['category', 'secondaryCategories', 'gallery', 'logo']);
        });
    }

    /** Publier ou dépublier depuis la liste. */
    public function togglePublication(Logiciel $logiciel): Logiciel
    {
        return DB::transaction(function () use ($logiciel) {
            $was = $logiciel->is_published;
            $logiciel->update(['is_published' => ! $was]);
            $this->logPublication($logiciel, $was, $logiciel->status);

            return $logiciel;
        });
    }

    private function logPublication(Logiciel $logiciel, bool $wasPublished, ?LogicielStatus $oldStatus): void
    {
        if ($logiciel->is_published !== $wasPublished) {
            $action = $logiciel->is_published ? 'published' : 'unpublished';
            $this->logger->log("logiciels.{$action}", 'logiciels', ($logiciel->is_published ? 'Publication' : 'Dépublication')." — Logiciel « {$logiciel->name} »", $logiciel);
        }
        if ($oldStatus !== null && $oldStatus !== $logiciel->status && $logiciel->status === LogicielStatus::Archive) {
            $this->logger->log('logiciels.archived', 'logiciels', "Archivage — Logiciel « {$logiciel->name} »", $logiciel);
        }
    }
}
