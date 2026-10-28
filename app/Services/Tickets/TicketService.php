<?php

namespace App\Services\Tickets;

use App\Enums\ActivityChannel;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Mail\TicketForClient;
use App\Mail\TicketForTeam;
use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\SequenceService;
use App\Services\Push\PushNotifier;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cycle d'un ticket de support : ouverture par le client (espace client) ou par l'utilisateur d'une application
 * connectée (API), échanges (réponses publiques, notes internes de l'équipe), statut, priorité, attribution,
 * notifications e-mail, WhatsApp et push (un échec d'envoi est journalisé, jamais bloquant).
 */
class TicketService
{
    public const ATTACHMENT_MIMES = 'pdf,doc,docx,xls,xlsx,odt,ods,jpg,jpeg,png,webp,txt,log,zip';

    public function __construct(private readonly SequenceService $sequences, private readonly ActivityLogger $logger, private readonly SiteSettings $settings) {}

    /** @param array{subject: string, category: string, priority: string, device?: ?string, message: string} $data */
    public function open(Client $client, array $data, ?UploadedFile $attachment = null): Ticket
    {
        $ticket = DB::transaction(function () use ($client, $data, $attachment) {
            $ticket = Ticket::create([
                'number' => $this->sequences->next('TCK'), 'client_id' => $client->id, 'subject' => $data['subject'],
                'category' => TicketCategory::from($data['category']), 'priority' => TicketPriority::from($data['priority']),
                'status' => TicketStatus::Ouvert, 'device' => $data['device'] ?? null, 'last_activity_at' => now(), 'awaiting_team' => true,
            ]);
            $this->addMessage($ticket, $data['message'], client: $client, attachment: $attachment);
            $this->logger->log('tickets.opened', 'tickets', "Ticket ouvert par le client — {$ticket->number} : {$ticket->subject}", $ticket, actor: null);

            return $ticket;
        });

        $this->announce($ticket);

        return $ticket;
    }

    /**
     * Ticket ouvert depuis une application connectée (KalanNet…) au nom de l'un de ses utilisateurs, sans compte client.
     *
     * @param  array{subject: string, category: string, priority: string, device?: ?string, message: string, reporter: array{ref: string, name: string, role?: ?string, organization?: ?string, email?: ?string, phone?: ?string}}  $data
     */
    public function openExternal(ApiToken $app, array $data, ?UploadedFile $attachment = null): Ticket
    {
        $ticket = DB::transaction(function () use ($app, $data, $attachment) {
            $reporter = $data['reporter'];
            $ticket = Ticket::create([
                'number' => $this->sequences->next('TCK'), 'source_app' => $app->name, 'api_token_id' => $app->id,
                'reporter_ref' => $reporter['ref'], 'reporter_name' => $reporter['name'], 'reporter_role' => $reporter['role'] ?? null,
                'reporter_org' => $reporter['organization'] ?? null, 'reporter_email' => isset($reporter['email']) ? mb_strtolower($reporter['email']) : null,
                'reporter_phone' => $reporter['phone'] ?? null, 'subject' => $data['subject'],
                'category' => TicketCategory::from($data['category']), 'priority' => TicketPriority::from($data['priority']),
                'status' => TicketStatus::Ouvert, 'device' => $data['device'] ?? null, 'last_activity_at' => now(), 'awaiting_team' => true,
            ]);
            $this->addMessage($ticket, $data['message'], fromReporter: true, attachment: $attachment);
            $this->logger->log('tickets.opened', 'tickets', "Ticket ouvert depuis {$app->name} — {$ticket->number} : {$ticket->subject}", $ticket, actor: null, channel: ActivityChannel::Api);

            return $ticket;
        });

        $this->announce($ticket);

        return $ticket;
    }

    /** Réponse de l'utilisateur d'une application connectée (même effet qu'une réponse du client). */
    public function reporterReply(Ticket $ticket, string $body, ?UploadedFile $attachment = null): TicketMessage
    {
        $message = DB::transaction(function () use ($ticket, $body, $attachment) {
            $message = $this->addMessage($ticket, $body, fromReporter: true, attachment: $attachment);
            $this->reopenForTeam($ticket);

            return $message;
        });

        $this->notifyTeam($ticket, 'Réponse du client');
        $this->push($ticket, "Réponse sur {$ticket->number}");

        return $message;
    }

    public function clientReply(Ticket $ticket, Client $client, string $body, ?UploadedFile $attachment = null): TicketMessage
    {
        $message = DB::transaction(function () use ($ticket, $client, $body, $attachment) {
            $message = $this->addMessage($ticket, $body, client: $client, attachment: $attachment);
            $this->reopenForTeam($ticket);

            return $message;
        });

        $this->notifyTeam($ticket, 'Réponse du client');
        $this->push($ticket, "Réponse sur {$ticket->number}");

        return $message;
    }

    /** Le demandeur relance : le ticket revient à l'équipe (un ticket résolu ou fermé se rouvre). */
    private function reopenForTeam(Ticket $ticket): void
    {
        $ticket->update([
            'status' => in_array($ticket->status, [TicketStatus::AttenteClient, TicketStatus::Resolu, TicketStatus::Ferme], true) ? TicketStatus::EnCours : $ticket->status,
            'awaiting_team' => true, 'last_activity_at' => now(), 'closed_at' => null,
        ]);
    }

    /** Nouveau ticket : e-mail, WhatsApp et notification push à l'équipe. */
    private function announce(Ticket $ticket): void
    {
        $from = $ticket->requesterName().($ticket->isExternal() ? " ({$ticket->source_app})" : '');
        $this->notifyTeam($ticket, 'Nouveau ticket');
        app(WhatsAppNotifier::class)->alert('tickets', "Nouveau ticket {$ticket->number} de {$from} : {$ticket->subject} (urgence : {$ticket->priority->label()})");
        $this->push($ticket, "Nouveau ticket {$ticket->number}");
    }

    private function push(Ticket $ticket, string $title): void
    {
        $from = $ticket->requesterName().($ticket->isExternal() ? " · {$ticket->source_app}" : '');
        app(PushNotifier::class)->notifyTeam('tickets.view', $title, "{$ticket->subject}\n{$from} · urgence {$ticket->priority->label()}", route('tickets.show', $ticket), $ticket->number);
    }

    /**
     * Réponse de l'équipe : publique (le client est prévenu) ou note interne. Le statut peut changer en même temps.
     */
    public function teamReply(Ticket $ticket, User $user, string $body, bool $internal, ?TicketStatus $status = null, ?UploadedFile $attachment = null): TicketMessage
    {
        $message = DB::transaction(function () use ($ticket, $user, $body, $internal, $status, $attachment) {
            $message = $this->addMessage($ticket, $body, user: $user, internal: $internal, attachment: $attachment);
            $changes = ['last_activity_at' => now(), 'assigned_to' => $ticket->assigned_to ?? $user->id];
            if (! $internal) {
                $changes['awaiting_team'] = false;
            }
            $ticket->update($changes);
            if ($status !== null && $status !== $ticket->status) {
                $this->changeStatus($ticket, $status, $user, notify: false);
            }

            return $message;
        });

        if (! $internal) {
            $this->notifyClient($ticket, $message);
        }

        return $message;
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, User $user, bool $notify = true): void
    {
        $old = $ticket->status;
        if ($old === $status) {
            return;
        }

        $ticket->update(['status' => $status, 'closed_at' => $status->isOpen() ? null : now(), 'awaiting_team' => $status->isOpen() && $ticket->awaiting_team]);
        $this->logger->log('tickets.status_changed', 'tickets', "Statut — {$ticket->number} : {$old->teamLabel()} → {$status->teamLabel()}", $ticket, ['status' => $old->value], ['status' => $status->value], $user);

        if ($notify) {
            $this->notifyClient($ticket, null);
        }
    }

    /** Priorité et personne chargée du ticket (null = personne). */
    public function update(Ticket $ticket, TicketPriority $priority, ?User $assignee): void
    {
        $ticket->update(['priority' => $priority, 'assigned_to' => $assignee?->id]);
    }

    private function addMessage(Ticket $ticket, string $body, ?Client $client = null, ?User $user = null, bool $internal = false, ?UploadedFile $attachment = null, bool $fromReporter = false): TicketMessage
    {
        $path = $attachment?->storeAs('tickets/'.$ticket->id, Str::uuid().'.'.$attachment->extension(), 'local');

        return $ticket->messages()->create([
            'client_id' => $client?->id, 'user_id' => $user?->id, 'from_reporter' => $fromReporter, 'body' => trim($body), 'is_internal' => $internal,
            'attachment_path' => $path ?: null, 'attachment_name' => $attachment ? Str::limit($attachment->getClientOriginalName(), 250, '') : null,
            'attachment_size' => $attachment?->getSize(),
        ]);
    }

    private function notifyTeam(Ticket $ticket, string $event): void
    {
        $recipient = $this->settings->notificationEmail();
        if (! $recipient) {
            return;
        }
        $this->send(fn () => Mail::to($recipient)->send(new TicketForTeam($ticket->fresh(['client', 'messages']), $event)), $ticket);
    }

    private function notifyClient(Ticket $ticket, ?TicketMessage $message): void
    {
        $email = $ticket->requesterEmail();
        if (! $email) {
            return;
        }
        $this->send(fn () => Mail::to($email)->send(new TicketForClient($ticket->fresh('client'), $message)), $ticket);
    }

    private function send(callable $sender, Ticket $ticket): void
    {
        try {
            $sender();
        } catch (Throwable $e) {
            Log::error("Notification du ticket {$ticket->number} impossible : ".$e->getMessage());
            $this->logger->log('tickets.notification_failed', 'tickets', "Échec de la notification e-mail — {$ticket->number}", $ticket, new: ['error' => Str::limit($e->getMessage(), 300)], actor: null);
        }
    }
}
