<?php

namespace App\Services\Statistiques;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Demande;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fréquentation du site (table `page_vues`, VisitTracker) et conversion. Une « visite » = un visiteur un jour donné
 * (l'empreinte anonyme change chaque jour) ; sa provenance est celle de la première page vue ce jour-là.
 */
class SiteStats
{
    public const PERIODS = [7 => '7 derniers jours', 30 => '30 derniers jours', 90 => '3 derniers mois', 365 => '12 derniers mois'];

    public const SOURCES = [
        'direct' => 'Accès direct (adresse tapée, favori, application)', 'google' => 'Google', 'moteur' => 'Autres moteurs de recherche',
        'facebook' => 'Facebook / Messenger', 'whatsapp' => 'WhatsApp', 'reseaux' => 'Autres réseaux sociaux (TikTok, Instagram…)',
        'partenaire' => 'Lien d\'un partenaire', 'autre_site' => 'Autres sites', 'interne' => 'Depuis le site lui-même',
    ];

    public const DEVICES = ['telephone' => 'Téléphone', 'ordinateur' => 'Ordinateur', 'tablette' => 'Tablette'];

    /** Noms lisibles des pages publiques (sinon l'adresse s'affiche). */
    private const PAGES = [
        'site.home' => 'Accueil', 'site.about' => 'À propos', 'site.services' => 'Nos services', 'site.logiciels.index' => 'Nos solutions (liste)',
        'site.materiels.index' => 'Matériel (catalogue)', 'site.realisations.index' => 'Nos réalisations', 'site.actualites.index' => 'Actualités',
        'site.contact' => 'Contact', 'site.devis' => 'Demander un devis', 'site.conseiller' => 'Choisir mon ordinateur', 'site.panier' => 'Panier',
        'site.commande' => 'Commande', 'site.temoignages.index' => 'Témoignages', 'site.search' => 'Recherche',
    ];

    /** Visites du jour, des 7 et 30 derniers jours, et comptes clients (tableau de bord). @return array<string, int> */
    public function summary(): array
    {
        return [
            'today' => $this->visits(today(), today()),
            'week' => $this->visits(today()->subDays(6), today()),
            'month' => $this->visits(today()->subDays(29), today()),
            'clients' => Client::count(),
            'clients_month' => Client::where('created_at', '>=', today()->subDays(29))->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function report(int $days): array
    {
        $days = array_key_exists($days, self::PERIODS) ? $days : 30;
        $from = today()->subDays($days - 1);
        $to = today();
        $previousFrom = $from->copy()->subDays($days);
        $previousTo = $from->copy()->subDay();

        $visits = $this->visits($from, $to);
        $views = DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->count();
        $clients = Client::whereBetween('created_at', [$from, $to->copy()->endOfDay()])->count();
        $demandes = Demande::whereBetween('created_at', [$from, $to->copy()->endOfDay()])->count();
        $commandes = Commande::whereBetween('created_at', [$from, $to->copy()->endOfDay()])->count();

        return [
            'days' => $days,
            'label' => self::PERIODS[$days],
            'visits' => $visits,
            'visits_change' => $this->change($visits, $this->visits($previousFrom, $previousTo)),
            'views' => $views,
            'views_per_visit' => $visits > 0 ? round($views / $visits, 1) : 0,
            'clients' => $clients,
            'clients_change' => $this->change($clients, Client::whereBetween('created_at', [$previousFrom, $previousTo->copy()->endOfDay()])->count()),
            'clients_total' => Client::count(),
            'demandes' => $demandes,
            'commandes' => $commandes,
            'conversion' => $visits > 0 ? round(100 * ($demandes + $commandes) / $visits, 1) : null,
            'chart' => $this->chart($from, $to, $days),
            'pages' => $this->pages($from, $to),
            'sources' => $this->breakdown($from, $to, 'source', self::SOURCES),
            'devices' => $this->breakdown($from, $to, 'device', self::DEVICES),
            'referrers' => DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
                ->whereNotNull('referrer_host')->whereNotIn('source', ['interne'])
                ->selectRaw('referrer_host, COUNT(DISTINCT day, visitor) as total')->groupBy('referrer_host')->orderByDesc('total')->limit(10)->pluck('total', 'referrer_host')->all(),
            'recent_clients' => Client::latest('id')->limit(8)->get(['id', 'name', 'organization', 'created_at']),
        ];
    }

    private function visits(Carbon $from, Carbon $to): int
    {
        return (int) DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COUNT(DISTINCT day, visitor) as total')->value('total');
    }

    /** Évolution en % par rapport à la période précédente (null : rien à comparer). */
    private function change(int $now, int $before): ?int
    {
        return $before > 0 ? (int) round(100 * ($now - $before) / $before) : null;
    }

    /**
     * Visites et pages vues par jour (7 et 30 jours), par semaine (3 mois) ou par mois (12 mois), au format du graphique
     * du tableau de bord (dashboard._activity-chart).
     *
     * @return array<string, mixed>
     */
    private function chart(Carbon $from, Carbon $to, int $days): array
    {
        $rows = DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('day, COUNT(DISTINCT visitor) as visits, COUNT(*) as views')->groupBy('day')->get()->keyBy(fn ($r) => Carbon::parse($r->day)->toDateString());

        $unit = $days <= 30 ? 'day' : ($days <= 90 ? 'week' : 'month');
        $buckets = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = match ($unit) {
                'day' => $day->toDateString(), 'week' => $day->copy()->startOfWeek()->toDateString(), 'month' => $day->format('Y-m')
            };
            $buckets[$key] ??= ['date' => $key, 'label' => match ($unit) {
                'day' => $day->format('d/m'), 'week' => 'sem. du '.$day->copy()->startOfWeek()->format('d/m'), 'month' => ucfirst($day->translatedFormat('M Y')),
            }, 'a' => 0, 'b' => 0];
            $row = $rows->get($day->toDateString());
            $buckets[$key]['a'] += (int) ($row->visits ?? 0);
            $buckets[$key]['b'] += (int) ($row->views ?? 0);
        }
        $buckets = array_values($buckets);
        // Axe lisible : au plus une quinzaine d'étiquettes.
        $every = max(1, (int) ceil(count($buckets) / 15));
        foreach ($buckets as $i => &$bucket) {
            $bucket['tick'] = $i % $every === 0 ? ($unit === 'week' ? Carbon::parse($bucket['date'])->format('d/m') : $bucket['label']) : '';
        }
        unset($bucket);

        return [
            'title' => 'Fréquentation — '.mb_strtolower(self::PERIODS[$days]),
            'empty' => 'Aucune visite enregistrée sur cette période.',
            'series' => ['a' => 'Visites', 'b' => 'Pages vues'],
            'days' => $buckets,
            'max' => max(1, ...array_map(fn ($b) => max($b['a'], $b['b']), $buckets)),
            'totals' => ['a' => array_sum(array_column($buckets, 'a')), 'b' => array_sum(array_column($buckets, 'b'))],
        ];
    }

    /** @return list<array{label: string, path: string, views: int, visits: int}> */
    private function pages(Carbon $from, Carbon $to): array
    {
        return DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('path, MAX(route) as route, COUNT(*) as views, COUNT(DISTINCT day, visitor) as visits')
            ->groupBy('path')->orderByDesc('views')->limit(12)->get()
            ->map(fn ($r) => ['label' => self::PAGES[$r->route] ?? $r->path, 'path' => $r->path, 'views' => (int) $r->views, 'visits' => (int) $r->visits])->all();
    }

    /**
     * Répartition des visites (première page vue du jour de chaque visiteur) par provenance ou par appareil.
     *
     * @param  array<string, string>  $labels
     * @return list<array{key: string, label: string, total: int, percent: int}>
     */
    private function breakdown(Carbon $from, Carbon $to, string $column, array $labels): array
    {
        $entries = DB::table('page_vues')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('MIN(id) as id')->groupBy('day', 'visitor');
        $counts = DB::table('page_vues')->whereIn('id', $entries)->selectRaw("{$column} as k, COUNT(*) as total")->groupBy('k')->pluck('total', 'k');
        $sum = max(1, (int) $counts->sum());

        return $counts->sortDesc()->map(fn ($total, $key) => ['key' => $key, 'label' => $labels[$key] ?? $key, 'total' => (int) $total, 'percent' => (int) round(100 * $total / $sum)])->values()->all();
    }
}
