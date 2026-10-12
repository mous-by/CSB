<?php

namespace App\Services\Partenaires;

use App\Enums\CommissionKind;
use App\Enums\CommissionStatus;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Materiel;
use App\Models\Partenaire;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Commissions des partenaires : montant encaissé × taux du partenaire pour le type de vente (figé au moment du calcul).
 * Commande de la boutique : calculée automatiquement quand elle passe « Payée » (ou « Livrée »), annulée si la commande
 * l'est. Logiciel, sur mesure ou vente hors boutique : enregistrée par l'équipe avec le montant encaissé.
 */
class CommissionService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /** Commission d'une commande payée (une seule par commande ; rien sans partenaire ou à taux nul). */
    public function forCommande(Commande $commande, ?User $user): ?Commission
    {
        $partenaire = $commande->partenaire;
        if (! $partenaire || Commission::where('source_type', $commande->getMorphClass())->where('source_id', $commande->id)->where('status', '!=', CommissionStatus::Annulee->value)->exists()) {
            return null;
        }

        return $this->record($partenaire, CommissionKind::Materiel, $commande->total, "Commande {$commande->number}", $commande, $user);
    }

    /** Annule la commission (non payée) liée à une vente annulée. */
    public function cancelFor(Model $source, User $user, string $reason): void
    {
        Commission::where('source_type', $source->getMorphClass())->where('source_id', $source->getKey())->where('status', CommissionStatus::APayer->value)
            ->get()->each(fn (Commission $c) => $c->update(['status' => CommissionStatus::Annulee, 'notes' => $reason]));
    }

    public function record(Partenaire $partenaire, CommissionKind $kind, int $base, string $label, ?Model $source, ?User $user, ?string $notes = null): ?Commission
    {
        $rate = $partenaire->rateFor($kind);
        if ($rate <= 0 || $base <= 0) {
            return null;
        }

        $commission = Commission::create([
            'partenaire_id' => $partenaire->id, 'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(),
            'kind' => $kind, 'label' => $label, 'base_amount' => $base, 'rate' => $rate, 'amount' => (int) round($base * $rate / 100),
            'status' => CommissionStatus::APayer, 'notes' => $notes, 'created_by' => $user?->id,
        ]);
        $this->logger->log('partenaires.commission', 'partenaires', "Commission de {$partenaire->name} : {$label} ({$rate} %)", $commission, actor: $user);

        return $commission;
    }

    /**
     * Paiement d'un lot de commissions « à payer » d'un partenaire.
     *
     * @param  list<int>  $ids
     */
    public function markPaid(Partenaire $partenaire, array $ids, string $reference, User $user): int
    {
        return DB::transaction(function () use ($partenaire, $ids, $reference, $user) {
            $commissions = $partenaire->commissions()->whereKey($ids)->where('status', CommissionStatus::APayer->value)->lockForUpdate()->get();
            if ($commissions->isEmpty()) {
                throw ValidationException::withMessages(['ids' => 'Aucune commission à payer sélectionnée.']);
            }
            $commissions->each(fn (Commission $c) => $c->update(['status' => CommissionStatus::Payee, 'paid_at' => now(), 'payment_reference' => $reference]));
            $total = (int) $commissions->sum('amount');
            $this->logger->log('partenaires.paid', 'partenaires', "Paiement de {$commissions->count()} commission(s) à {$partenaire->name} : ".Materiel::money($total)." (réf. {$reference})", $partenaire, actor: $user);

            return $total;
        });
    }
}
