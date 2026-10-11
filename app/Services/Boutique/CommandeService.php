<?php

namespace App\Services\Boutique;

use App\Enums\CommandeStatus;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Mail\CommandeForClient;
use App\Mail\CommandeForTeam;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\Materiel;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Numbering\SequenceService;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\PhoneNumber;
use App\Support\SiteSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Commandes de la boutique : enregistrement depuis le panier (numéro CMD-AAAA-NNNN, prix figés), suivi par l'équipe
 * (confirmée, payée avec la référence de la transaction, livrée, annulée avec motif), e-mails à l'équipe et au client
 * (un échec d'envoi est journalisé, jamais bloquant).
 */
class CommandeService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
        private readonly SiteSettings $settings,
    ) {}

    /**
     * @param  array{name: string, organization?: ?string, email?: ?string, phone: string, delivery_method: string, address?: ?string, city?: ?string, payment_method: string, notes?: ?string}  $data
     * @param  Collection<int, array{materiel: Materiel, quantity: int, unit_price: int, normal_price: int, line_total: int}>  $lines
     */
    public function place(array $data, Collection $lines, ?Client $client, ?string $ip): Commande
    {
        if ($lines->isEmpty()) {
            throw new RuntimeException('Le panier est vide.');
        }

        // Audit : une seule entrée explicite par action (« commande passée », « statut »), pas la journalisation automatique.
        $commande = DB::transaction(fn () => Commande::withoutActivityLog(fn () => CommandeLigne::withoutActivityLog(function () use ($data, $lines, $client, $ip) {
            $commande = Commande::create([
                'number' => $this->sequences->next('CMD'), 'client_id' => $client?->id, 'status' => CommandeStatus::Nouvelle,
                'name' => $data['name'], 'organization' => $data['organization'] ?? null, 'email' => isset($data['email']) ? Str::lower($data['email']) : null,
                'phone' => PhoneNumber::normalize($data['phone']) ?? $data['phone'],
                'delivery_method' => DeliveryMethod::from($data['delivery_method']), 'address' => $data['address'] ?? null, 'city' => $data['city'] ?? null,
                'payment_method' => PaymentMethod::from($data['payment_method']), 'notes' => $data['notes'] ?? null,
                'total' => $lines->sum('line_total'), 'ip_address' => $ip, 'access_token' => Str::random(48),
            ]);
            foreach ($lines as $line) {
                $commande->lignes()->create([
                    'materiel_id' => $line['materiel']->id, 'designation' => $line['materiel']->designation, 'unit_price' => $line['unit_price'],
                    'normal_price' => $line['normal_price'] !== $line['unit_price'] ? $line['normal_price'] : null,
                    'quantity' => $line['quantity'], 'line_total' => $line['line_total'],
                ]);
            }
            $this->logger->log('commandes.placed', 'commandes', "Commande passée sur le site — {$commande->number} ({$commande->totalForDisplay()})", $commande, actor: null);

            return $commande;
        })));

        $commande->load('lignes');
        $recipient = $this->settings->notificationEmail();
        if ($recipient) {
            $this->send(fn () => Mail::to($recipient)->send(new CommandeForTeam($commande)), $commande);
        }
        $this->notifyClient($commande);
        app(WhatsAppNotifier::class)->alert('commandes', "Nouvelle commande {$commande->number} de {$commande->name} — {$commande->phone} — {$commande->totalForDisplay()}");

        return $commande;
    }

    /** Changement de statut par l'équipe ; référence de paiement au passage à « payée », motif à l'annulation. */
    public function changeStatus(Commande $commande, CommandeStatus $status, User $user, ?string $paymentReference = null, ?string $reason = null): void
    {
        $old = $commande->status;
        if ($old === $status && $paymentReference === null) {
            return;
        }

        $changes = ['status' => $status, 'handled_by' => $commande->handled_by ?? $user->id];
        match ($status) {
            CommandeStatus::Confirmee => $changes['confirmed_at'] = $commande->confirmed_at ?? now(),
            CommandeStatus::Payee => $changes += ['paid_at' => $commande->paid_at ?? now(), 'confirmed_at' => $commande->confirmed_at ?? now()],
            CommandeStatus::Livree => $changes['delivered_at'] = now(),
            CommandeStatus::Annulee => $changes['cancel_reason'] = $reason,
            default => null,
        };
        if ($paymentReference !== null && $paymentReference !== '') {
            $changes['payment_reference'] = $paymentReference;
        }

        Commande::withoutActivityLog(fn () => $commande->update($changes));
        if ($old !== $status) {
            $this->logger->log('commandes.status_changed', 'commandes', "Commande {$commande->number} : {$old->label()} → {$status->label()}", $commande, ['status' => $old->value], ['status' => $status->value], $user);
            $this->notifyClient($commande->fresh('lignes'));
        } else {
            $this->logger->log('commandes.payment_recorded', 'commandes', "Commande {$commande->number} : référence de paiement {$paymentReference}", $commande, new: ['payment_reference' => $paymentReference], actor: $user);
        }
    }

    /** Instructions de paiement à afficher au client (numéro marchand du moyen choisi). */
    public function paymentInstructions(Commande $commande): string
    {
        $key = $commande->payment_method->settingKey();
        if ($key === null) {
            return $commande->delivery_method === DeliveryMethod::Retrait
                ? 'Réglez en espèces au retrait de votre commande au magasin.'
                : 'Réglez en espèces à la livraison.';
        }
        $number = trim($this->settings->get($key));

        return $number !== ''
            ? "Après notre appel de confirmation, envoyez {$commande->totalForDisplay()} par {$commande->payment_method->label()} au {$number} en indiquant la référence {$commande->number}."
            : "Après notre appel de confirmation, nous vous communiquons le numéro {$commande->payment_method->label()} pour le paiement de {$commande->totalForDisplay()} (référence {$commande->number}).";
    }

    private function notifyClient(Commande $commande): void
    {
        $email = $commande->email ?: $commande->client?->email;
        if ($email) {
            $this->send(fn () => Mail::to($email)->send(new CommandeForClient($commande)), $commande);
        }
    }

    private function send(callable $sender, Commande $commande): void
    {
        try {
            $sender();
        } catch (Throwable $e) {
            Log::error("Notification de la commande {$commande->number} impossible : ".$e->getMessage());
            $this->logger->log('commandes.notification_failed', 'commandes', "Échec de la notification e-mail — {$commande->number}", $commande, new: ['error' => Str::limit($e->getMessage(), 300)], actor: null);
        }
    }
}
