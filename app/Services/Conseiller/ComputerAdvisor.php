<?php

namespace App\Services\Conseiller;

use App\Enums\AdvisorBudget;
use App\Enums\AdvisorForm;
use App\Enums\AdvisorProfile;
use App\Enums\AdvisorUsage;
use App\Enums\Availability;
use App\Enums\MaterielCondition;
use App\Models\Materiel;
use Illuminate\Support\Collection;

/**
 * Conseiller d'achat : à partir du métier, des usages, du format, du budget et de l'état souhaités, propose les
 * ordinateurs publiés dont l'équipe a coché les usages (fiche matériel → « Conseiller d'achat »), du plus adapté au
 * moins adapté, avec les raisons du choix en mots simples. Un matériel indisponible n'est jamais proposé.
 */
class ComputerAdvisor
{
    /** Au-delà du budget, un modèle reste proposé (signalé) jusqu'à +20 %. */
    private const OVER_BUDGET_TOLERANCE = 1.2;

    public const MAX_RESULTS = 6;

    /**
     * @param  list<AdvisorUsage>  $extraUsages
     * @return array{usages: list<AdvisorUsage>, level: int, matches: Collection<int, array{materiel: Materiel, score: int, reasons: list<string>, warnings: list<string>}>, cheapest_above: ?int}
     */
    public function recommend(?AdvisorProfile $profile, array $extraUsages, ?AdvisorForm $form, AdvisorBudget $budget, bool $newOnly): array
    {
        $usages = collect($profile?->usages() ?? [])->merge($extraUsages)->unique(fn (AdvisorUsage $u) => $u->value)->values();
        if ($usages->isEmpty()) {
            $usages = collect([AdvisorUsage::Bureautique]);
        }
        $needed = $usages->map->value->all();

        $candidates = Materiel::published()->whereNotNull('advisor_usages')->where('availability', '!=', Availability::Indisponible->value)
            ->with(['mainImage', 'gallery'])->get();

        $scored = $candidates->map(fn (Materiel $m) => $this->score($m, $needed, $form, $budget, $newOnly))->filter()->values();

        $matches = $scored->sortBy([['score', 'desc'], [fn ($a, $b) => ($a['materiel']->currentPrice() ?? PHP_INT_MAX) <=> ($b['materiel']->currentPrice() ?? PHP_INT_MAX)]])
            ->take(self::MAX_RESULTS)->values();

        // Rien dans le budget : le prix du premier modèle adapté, pour que le visiteur sache à quoi s'attendre.
        $cheapestAbove = null;
        if ($matches->isEmpty() && $budget->max() !== null) {
            $cheapestAbove = $candidates->filter(fn (Materiel $m) => $this->score($m, $needed, $form, AdvisorBudget::Inconnu, $newOnly) !== null && $m->currentPrice() !== null)
                ->min(fn (Materiel $m) => $m->currentPrice());
        }

        return ['usages' => $usages->all(), 'level' => $usages->max(fn (AdvisorUsage $u) => $u->level()), 'matches' => $matches, 'cheapest_above' => $cheapestAbove];
    }

    /**
     * @param  list<string>  $needed
     * @return ?array{materiel: Materiel, score: int, reasons: list<string>, warnings: list<string>}
     */
    private function score(Materiel $materiel, array $needed, ?AdvisorForm $form, AdvisorBudget $budget, bool $newOnly): ?array
    {
        $covered = array_values(array_intersect($needed, $materiel->advisor_usages ?? []));
        if ($covered === [] || ($newOnly && $materiel->condition !== MaterielCondition::Neuf)) {
            return null;
        }
        if ($form && $materiel->advisor_form && $materiel->advisor_form !== $form) {
            return null;
        }

        $score = (int) round(100 * count($covered) / count($needed));
        $labels = array_map(fn ($u) => AdvisorUsage::from($u)->label(), $covered);
        $reasons = ['Convient pour : '.implode(', ', $labels).'.'];
        $warnings = [];

        $missing = array_diff($needed, $covered);
        if ($missing !== []) {
            $warnings[] = 'Moins adapté pour : '.implode(', ', array_map(fn ($u) => AdvisorUsage::from($u)->label(), $missing)).'.';
        }

        if ($form && $materiel->advisor_form === $form) {
            $reasons[] = 'Format '.mb_strtolower($form->label()).', comme vous le souhaitez.';
        } elseif ($form) {
            $score -= 10;
        }

        $price = $materiel->currentPrice();
        if ($budget->max() !== null || $budget->min() !== null) {
            if ($price === null) {
                $score -= 15;
                $warnings[] = 'Prix sur demande : CSB vous l\'indique rapidement.';
            } elseif ($budget->max() !== null && $price > $budget->max()) {
                if ($price > $budget->max() * self::OVER_BUDGET_TOLERANCE) {
                    return null;
                }
                $score -= 30;
                $warnings[] = 'Un peu au-dessus de votre budget ('.Materiel::money($price - $budget->max()).' de plus).';
            } else {
                $reasons[] = 'Dans votre budget.';
            }
        }

        if ($materiel->isOnPromo()) {
            $score += 5;
            $reasons[] = 'En promotion en ce moment.';
        }
        if ($materiel->condition === MaterielCondition::Reconditionne) {
            $reasons[] = 'Reconditionné : moins cher qu\'un neuf, contrôlé avant la vente.';
        }
        if ($materiel->is_featured) {
            $score += 3;
        }

        return ['materiel' => $materiel, 'score' => $score, 'reasons' => $reasons, 'warnings' => $warnings];
    }
}
