<?php

namespace App\Services\Partenaires;

use App\Enums\CommandeStatus;
use App\Enums\CommissionStatus;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Demande;
use App\Models\Partenaire;
use App\Models\PartenaireVisite;

/** Résultats d'un partenaire : visites venues de son lien, demandes, comptes, commandes, ventes et commissions. */
class PartnerStats
{
    /** @return array<string, mixed> */
    public function for(Partenaire $partenaire, int $days = 30): array
    {
        $from = today()->subDays($days - 1);
        $visits = PartenaireVisite::where('partenaire_id', $partenaire->id)->where('day', '>=', $from)->pluck('visits', 'day')
            ->mapWithKeys(fn ($v, $d) => [substr((string) $d, 0, 10) => (int) $v]);
        $series = collect(range(0, $days - 1))->map(fn ($i) => ['day' => $from->copy()->addDays($i), 'visits' => $visits[$from->copy()->addDays($i)->toDateString()] ?? 0]);
        $paid = Commande::where('partenaire_id', $partenaire->id)->whereIn('status', [CommandeStatus::Payee->value, CommandeStatus::Livree->value]);
        $commissions = Commission::where('partenaire_id', $partenaire->id)->selectRaw('status, SUM(amount) as total, COUNT(*) as n')->groupBy('status')->get()->keyBy(fn ($r) => $r->status->value);

        return [
            'days' => $days,
            'series' => $series,
            'visits_period' => (int) $series->sum('visits'),
            'visits_total' => (int) PartenaireVisite::where('partenaire_id', $partenaire->id)->sum('visits'),
            'demandes' => Demande::where('partenaire_id', $partenaire->id)->count(),
            'clients' => Client::where('partenaire_id', $partenaire->id)->count(),
            'commandes' => Commande::where('partenaire_id', $partenaire->id)->count(),
            'ventes' => (clone $paid)->count(),
            'chiffre' => (int) (clone $paid)->sum('total'),
            'a_payer' => (int) ($commissions[CommissionStatus::APayer->value]->total ?? 0),
            'payees' => (int) ($commissions[CommissionStatus::Payee->value]->total ?? 0),
            'a_payer_n' => (int) ($commissions[CommissionStatus::APayer->value]->n ?? 0),
        ];
    }
}
