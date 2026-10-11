<?php

namespace App\Services\Demandes;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Mail\NouvelleDemande;
use App\Models\Demande;
use App\Models\DemandeNote;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Cycle d'une demande (cahier §6) : réception depuis le site (enregistrement d'abord, notification e-mail ensuite : un
 * échec d'envoi ne perd jamais la demande et reste visible, D-4, REC-07), puis traitement (statut, attribution, notes),
 * chaque étape journalisée.
 */
class DemandeService
{
    public const ATTACHMENT_DIRECTORY = 'demandes';

    public function __construct(private readonly ActivityLogger $logger, private readonly SiteSettings $settings) {}

    /**
     * @param  array<string, mixed>  $data  données validées du formulaire
     */
    public function receive(DemandeType $type, array $data, Request $request, ?Model $subject = null, ?UploadedFile $attachment = null): Demande
    {
        $path = $attachment?->storeAs(self::ATTACHMENT_DIRECTORY.'/'.now()->format('Y/m'), Str::uuid().'.'.$attachment->extension(), 'local');

        try {
            $demande = DB::transaction(function () use ($type, $data, $request, $subject, $attachment, $path) {
                $demande = Demande::create([
                    'type' => $type,
                    'status' => DemandeStatus::Nouvelle,
                    'name' => $data['name'],
                    'organization' => $data['organization'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'subject' => $data['subject'] ?? null,
                    'message' => $data['message'] ?? null,
                    'need_type' => $data['need_type'] ?? null,
                    'budget' => $data['budget'] ?? null,
                    'deadline' => $data['deadline'] ?? null,
                    'availability' => $data['availability'] ?? null,
                    'subject_type' => $subject?->getMorphClass(),
                    'subject_id' => $subject?->getKey(),
                    'subject_label' => $subject ? Str::limit($subject->activityLabel(), 200, '') : null,
                    'source_url' => Str::limit((string) ($data['source_url'] ?? $request->headers->get('referer')), 500, '') ?: null,
                    'attachment_path' => $path ?: null,
                    'attachment_name' => $attachment ? Str::limit($attachment->getClientOriginalName(), 250, '') : null,
                    'attachment_size' => $attachment?->getSize(),
                    'consent_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                    // Client connecté à son espace : la demande y apparaît immédiatement.
                    'client_id' => $request->user('client')?->id,
                ]);

                $this->logger->log('demandes.received', 'demandes', "Demande reçue — {$type->label()} de {$demande->name}", $demande);

                return $demande;
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        $this->notify($demande);
        app(WhatsAppNotifier::class)->alert('demandes', "Nouvelle demande ({$type->label()}) de {$demande->name}".($demande->phone ? " — {$demande->phone}" : '').($demande->subject_label ? " — {$demande->subject_label}" : ''));

        return $demande;
    }

    /** Envoie la notification e-mail ; un échec est enregistré sur la demande et journalisé, jamais propagé. */
    public function notify(Demande $demande): bool
    {
        $recipient = $this->settings->notificationEmail();

        try {
            if (! $recipient) {
                throw new RuntimeException('Aucun destinataire configuré (SITE_NOTIFICATION_EMAIL ou e-mail de contact).');
            }

            Mail::to($recipient)->send(new NouvelleDemande($demande));
            $demande->forceFill(['notified_at' => now(), 'notification_error' => null])->saveQuietly();

            return true;
        } catch (Throwable $e) {
            $message = Str::limit($e->getMessage(), 480);
            $demande->forceFill(['notification_error' => $message])->saveQuietly();
            Log::error("Notification de la demande {$demande->reference()} impossible : {$message}");
            $this->logger->log('demandes.notification_failed', 'demandes', "Échec de la notification e-mail — {$demande->reference()}", $demande, new: ['error' => $message]);

            return false;
        }
    }

    /** Changement de statut (cahier §6.5) : motif facultatif à la clôture. */
    public function changeStatus(Demande $demande, DemandeStatus $status, User $actor, ?string $reason = null): Demande
    {
        return DB::transaction(function () use ($demande, $status, $actor, $reason) {
            $old = $demande->status;
            if ($old === $status && ($status !== DemandeStatus::Perdue || $reason === $demande->closed_reason)) {
                return $demande;
            }

            $demande->update([
                'status' => $status,
                'status_changed_at' => now(),
                'closed_reason' => $status === DemandeStatus::Perdue ? ($reason ?: null) : null,
                // Prise en charge : la demande est attribuée à celui qui la traite s'il n'y a personne.
                'assigned_to' => $demande->assigned_to ?? ($status !== DemandeStatus::Nouvelle ? $actor->id : null),
            ]);

            $this->logger->log('demandes.status_changed', 'demandes', "Statut — {$demande->reference()} : {$old->label()} → {$status->label()}", $demande,
                ['status' => $old->value], ['status' => $status->value] + ($reason ? ['reason' => $reason] : []), $actor);

            return $demande;
        });
    }

    public function assign(Demande $demande, ?User $user, User $actor): Demande
    {
        $demande->update(['assigned_to' => $user?->id]);

        return $demande;
    }

    public function addNote(Demande $demande, string $body, User $author): DemandeNote
    {
        return $demande->notes()->create(['user_id' => $author->id, 'body' => trim($body)]);
    }
}
