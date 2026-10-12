<?php

namespace App\Services\Temoignages;

use App\Mail\TemoignageRecu;
use App\Models\Client;
use App\Models\Temoignage;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Medias\MediaService;
use App\Services\Videos\ChunkedVideoUpload;
use App\Services\Videos\TestimonialVideoService;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Témoignages : saisie par l'équipe (texte, photo, vidéo envoyée ou lien) et envoi par un client depuis le site
 * (masqué jusqu'à la modération ; l'équipe est prévenue par e-mail et WhatsApp).
 */
class TemoignageService
{
    public function __construct(
        private readonly ChunkedVideoUpload $uploads,
        private readonly TestimonialVideoService $videos,
        private readonly MediaService $medias,
        private readonly ActivityLogger $logger,
    ) {}

    /** @param array<string, mixed> $data */
    public function submit(array $data, ?UploadedFile $photo, ?Client $client): Temoignage
    {
        $video = $this->uploads->resolve($data['video_token'] ?? null);
        if (! $video && blank($data['content'] ?? null)) {
            throw ValidationException::withMessages(['video_token' => 'La vidéo n\'a pas été reçue : envoyez-la de nouveau ou écrivez votre témoignage.']);
        }

        $temoignage = DB::transaction(function () use ($data, $photo, $client) {
            $temoignage = Temoignage::withoutActivityLog(fn () => Temoignage::create([
                'author_name' => $data['author_name'], 'author_title' => $data['author_title'] ?? null, 'organization' => $data['organization'] ?? null,
                'contact' => $data['contact'], 'rating' => $data['rating'] ?? null, 'content' => $data['content'] ?? null,
                'photo_media_id' => $photo ? $this->medias->store($photo, null, $data['author_name'], 'Témoignages')->id : null,
                'source' => 'client', 'client_id' => $client?->id, 'consent_at' => now(), 'is_published' => false, 'sort_order' => 0,
            ]));
            $this->logger->log('temoignages.submitted', 'temoignages', "Témoignage reçu du site — {$temoignage->author_name}", $temoignage, actor: null);

            return $temoignage;
        });
        if ($video) {
            $this->videos->attach($temoignage, $video);
        }

        $this->notifyTeam($temoignage->fresh());

        return $temoignage;
    }

    /** Création ou modification par l'équipe. @param array<string, mixed> $data */
    public function save(?Temoignage $temoignage, array $data, ?UploadedFile $photo, User $user): Temoignage
    {
        $video = $this->uploads->resolve($data['video_token'] ?? null);
        $fields = collect($data)->only(['author_name', 'author_title', 'organization', 'rating', 'content', 'video_url', 'sort_order', 'is_published', 'is_featured'])->all();
        if ($photo) {
            $fields['photo_media_id'] = $this->medias->store($photo, $user, $data['author_name'], 'Témoignages')->id;
        } elseif (! empty($data['remove_photo'])) {
            $fields['photo_media_id'] = null;
        }

        $temoignage = DB::transaction(function () use ($temoignage, $fields) {
            if ($temoignage) {
                $temoignage->update($fields);

                return $temoignage;
            }

            return Temoignage::create($fields + ['source' => 'equipe']);
        });

        if ($video) {
            $this->videos->attach($temoignage, $video);
        } elseif (! empty($data['remove_video']) && $temoignage->video_path) {
            $this->videos->remove($temoignage);
        }

        return $temoignage;
    }

    private function notifyTeam(Temoignage $temoignage): void
    {
        $recipient = app(SiteSettings::class)->notificationEmail();
        try {
            if ($recipient) {
                Mail::to($recipient)->send(new TemoignageRecu($temoignage));
            }
        } catch (Throwable $e) {
            Log::error('Notification du témoignage impossible : '.$e->getMessage());
        }
        app(WhatsAppNotifier::class)->alert('temoignages', 'Nouveau témoignage '.($temoignage->video_path ? 'en vidéo ' : '')."de {$temoignage->author_name}".($temoignage->organization ? " ({$temoignage->organization})" : '').' : à valider avant publication.');
    }
}
