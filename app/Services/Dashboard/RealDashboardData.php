<?php

namespace App\Services\Dashboard;

use App\Enums\CommandeStatus;
use App\Enums\CommissionStatus;
use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Demande;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Partenaire;
use App\Models\Realisation;
use App\Models\Temoignage;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Backup\DatabaseBackup;
use App\Services\Partenaires\PartnerStats;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Tableau de bord de l'administration (cahier §7 « résumé des demandes récentes, volumes par statut et raccourcis de
 * gestion ») : chaque rôle ne voit que ce qui le concerne, section par section, chacune derrière une permission
 * (jamais une donnée affichée sans le droit de la consulter) :
 *  - Comptes et accès (users.view) et Exploitation (exploitation.view) : l'administrateur ;
 *  - Supervision (superadmin seul) : activité par agent, journal, contrôle des comptes superadmin.
 * Les modules ajoutent leur section ici (demandes commerciales, contenus publiés).
 * Le superadmin, qui passe toutes les permissions (Gate::before), a donc la vue la plus large.
 */
class RealDashboardData
{
    /** Âge maximal d'une sauvegarde avant alerte (sauvegarde quotidienne + marge). */
    private const BACKUP_MAX_AGE_HOURS = 26;

    public function __construct(private readonly DatabaseBackup $backups, private readonly SiteSettings $settings) {}

    /**
     * @return array{heading: string, alerts: list<array<string, string>>, tasks: list<array<string, mixed>>, quick: list<array<string, mixed>>, chart: array<string, mixed>|null, sections: list<array<string, mixed>>}
     */
    public function for(User $user): array
    {
        $dashboard = ['heading' => $this->heading($user), 'alerts' => [], 'tasks' => [], 'quick' => $this->quick($user), 'chart' => null, 'sections' => []];

        if ($user->can('demandes.view')) {
            $this->demandes($user, $dashboard);
        }
        $this->partenaires($user, $dashboard);
        $this->contenus($user, $dashboard);
        if ($user->can('users.view')) {
            $this->comptes($user, $dashboard);
        }
        if ($user->can('exploitation.view')) {
            $this->exploitation($dashboard);
        }
        if ($user->isSuperadmin()) {
            $this->supervision($dashboard);
        }

        return $dashboard;
    }

    /**
     * Pastilles du menu : éléments qui attendent une action.
     *
     * @return array<string, int>
     */
    public function sidebarBadges(User $user): array
    {
        return array_filter([
            'demandes' => $user->can('demandes.view') ? Demande::where('status', DemandeStatus::Nouvelle->value)->count() : 0,
            'commandes' => $user->can('boutique.view') ? Commande::where('status', CommandeStatus::Nouvelle->value)->count() : 0,
            'temoignages' => $user->can('temoignages.update') ? Temoignage::where('source', 'client')->where('is_published', false)->count() : 0,
            'tickets' => $user->can('tickets.view') ? Ticket::open()->where('awaiting_team', true)->count() : 0,
        ]);
    }

    /**
     * Notifications de la cloche : uniquement ce que l'utilisateur peut traiter ou consulter.
     *
     * @return list<array{icon: string, class: string, text: string, link: string}>
     */
    public function notifications(User $user): array
    {
        $items = [];

        if ($user->can('demandes.view')) {
            $new = Demande::where('status', DemandeStatus::Nouvelle->value)->count();
            if ($new > 0 && Route::has('demandes.index')) {
                $items[] = ['icon' => 'bx-envelope', 'class' => 'text-danger', 'text' => "{$new} nouvelle(s) demande(s) à traiter.", 'link' => route('demandes.index')];
            }
            $failed = Demande::whereNotNull('notification_error')->where('created_at', '>=', now()->subDays(7))->count();
            if ($failed > 0) {
                $items[] = ['icon' => 'bx-error', 'class' => 'text-warning', 'text' => "{$failed} notification(s) e-mail non envoyée(s) cette semaine.", 'link' => route('demandes.index')];
            }
        }

        if ($user->can('boutique.view') && Route::has('commandes.index')) {
            $new = Commande::where('status', CommandeStatus::Nouvelle->value)->count();
            if ($new > 0) {
                $items[] = ['icon' => 'bx-cart', 'class' => 'text-danger', 'text' => "{$new} nouvelle(s) commande(s) à confirmer.", 'link' => route('commandes.index')];
            }
        }

        if ($user->can('temoignages.update') && Route::has('temoignages.index')) {
            $pending = Temoignage::where('source', 'client')->where('is_published', false)->count();
            if ($pending > 0) {
                $items[] = ['icon' => 'bx-video', 'class' => 'text-warning', 'text' => "{$pending} témoignage(s) de client à valider.", 'link' => route('temoignages.index')];
            }
        }

        if ($user->can('tickets.view') && Route::has('tickets.index')) {
            $waiting = Ticket::open()->where('awaiting_team', true)->count();
            if ($waiting > 0) {
                $items[] = ['icon' => 'bx-support', 'class' => 'text-danger', 'text' => "{$waiting} ticket(s) d'assistance en attente de réponse.", 'link' => route('tickets.index')];
            }
        }

        if ($user->can('exploitation.view')) {
            $backup = $this->backupState();
            if (! $backup['ok']) {
                $items[] = ['icon' => 'bx-data', 'class' => 'text-danger', 'text' => $backup['message'], 'link' => route('home')];
            }
        }

        return $items;
    }

    /** Demandes commerciales (cahier §7 « résumé des demandes récentes, volumes par statut ») : le commercial. */
    private function demandes(User $user, array &$dashboard): void
    {
        $byStatus = Demande::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $new = (int) ($byStatus[DemandeStatus::Nouvelle->value] ?? 0);
        $mine = Demande::open()->where('assigned_to', $user->id)->count();

        $dashboard['tasks'][] = $this->task('Nouvelles demandes', 'Reçues, pas encore prises en charge', $new, 'bx bx-envelope', 'danger', 'demandes.index');
        $dashboard['tasks'][] = $this->task('Mes demandes en cours', 'Attribuées à moi, non clôturées', $mine, 'bx bx-user-check', 'primary', 'demandes.index');

        $cards = [];
        foreach ([[DemandeStatus::Nouvelle, 'bx bx-envelope', 'danger'], [DemandeStatus::EnCours, 'bx bx-loader-circle', 'warning'], [DemandeStatus::DevisEnvoye, 'bx bx-send', 'info'], [DemandeStatus::Gagnee, 'bx bx-trophy', 'success']] as [$status, $icon, $color]) {
            $cards[] = $this->card($status->label(), (string) ($byStatus[$status->value] ?? 0), $status->meaning(), $icon, $color, $color === 'warning');
        }

        $month = Demande::where('created_at', '>=', now()->startOfMonth());
        $won = (int) (clone $month)->where('status', DemandeStatus::Gagnee->value)->count();
        $total = (int) (clone $month)->count();

        $tables = [
            [
                'title' => 'DERNIÈRES DEMANDES', 'icon' => 'bx bx-envelope',
                'columns' => ['Reçue le', 'Type', 'Demandeur', 'Statut'],
                'rows' => Demande::latest('id')->limit(8)->get()->map(fn (Demande $d) => [
                    $d->created_at->format('d/m/Y H:i'), $d->type->label(), $d->name, ['badge' => $d->status->label(), 'class' => $d->status->badge()],
                ])->all(),
            ],
            [
                'title' => 'CE MOIS-CI', 'icon' => 'bx bx-calendar',
                'columns' => ['Indicateur', 'Valeur'],
                'rows' => array_merge(
                    [['Demandes reçues', (string) $total], ['Gagnées', (string) $won], ['Taux de conversion', $total ? round($won * 100 / $total).' %' : '—']],
                    collect(DemandeType::cases())->map(fn (DemandeType $t) => [$t->label(), (string) (clone $month)->where('type', $t->value)->count()])->all(),
                ),
            ],
        ];

        $dashboard['sections'][] = ['title' => 'Demandes commerciales', 'icon' => 'bx bx-briefcase', 'cards' => $cards, 'tables' => $tables, 'chart' => $this->demandesChart(14)];
    }

    /** Demandes reçues et gagnées par jour (graphique du tableau de bord). */
    private function demandesChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $received = Demande::where('created_at', '>=', $from)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $won = Demande::where('status', DemandeStatus::Gagnee->value)->where('status_changed_at', '>=', $from)
            ->selectRaw('DATE(status_changed_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $series[] = ['date' => $key, 'label' => $day->format('d/m'), 'a' => (int) ($received[$key] ?? 0), 'b' => (int) ($won[$key] ?? 0)];
        }

        return [
            'title' => 'DEMANDES DES '.$days.' DERNIERS JOURS',
            'empty' => 'Aucune demande reçue sur les '.$days.' derniers jours.',
            'series' => ['a' => 'Demandes reçues', 'b' => 'Demandes gagnées'],
            'days' => $series,
            'max' => max(1, ...array_map(fn ($d) => max($d['a'], $d['b']), $series)),
            'totals' => ['a' => array_sum(array_column($series, 'a')), 'b' => array_sum(array_column($series, 'b'))],
        ];
    }

    /** Contenus du site (gestionnaire de contenu) : publiés, brouillons, images sans texte alternatif. */
    private function contenus(User $user, array &$dashboard): void
    {
        $modules = [
            ['logiciels.view', Logiciel::class, 'Logiciels', 'bx bx-code-block', 'primary', 'logiciels.index'],
            ['materiels.view', Materiel::class, 'Matériel', 'bx bx-laptop', 'info', 'materiels.index'],
            ['realisations.view', Realisation::class, 'Réalisations', 'bx bx-briefcase-alt-2', 'success', 'realisations.index'],
            ['articles.view', Article::class, 'Actualités', 'bx bx-news', 'dark', 'articles.index'],
        ];

        $cards = [];
        $drafts = [];
        foreach ($modules as [$permission, $model, $label, $icon, $color, $route]) {
            if (! $user->can($permission)) {
                continue;
            }
            $published = $model::where('is_published', true)->count();
            $draftCount = $model::where('is_published', false)->count();
            $cards[] = $this->card($label, (string) $published, $published > 1 ? 'publiés' : 'publié', $icon, $color);
            $drafts[] = [$label, (string) $draftCount, $route];
        }

        if ($cards === []) {
            return;
        }

        if ($user->can('medias.view')) {
            $withoutAlt = Media::whereNull('alt')->count();
            $dashboard['tasks'][] = $this->task('Images sans texte alternatif', 'Accessibilité et référencement', $withoutAlt, 'bx bx-image', 'warning', 'medias.index');
        }

        $tables = [[
            'title' => 'NON PUBLIÉS', 'icon' => 'bx bx-hide',
            'columns' => ['Contenu', 'Non publiés'],
            'rows' => array_map(fn ($row) => [$row[0], (int) $row[1] > 0 ? ['badge' => $row[1], 'class' => 'bg-warning-subtle'] : '0'], $drafts),
        ]];

        $dashboard['sections'][] = ['title' => 'Contenus du site', 'icon' => 'bx bx-globe', 'cards' => $cards, 'tables' => $tables];
    }

    /** Partenaires : ses propres résultats (rôle Communication) et commissions à payer (équipe). */
    private function partenaires(User $user, array &$dashboard): void
    {
        $own = $user->can('partenaires.dashboard') ? Partenaire::where('user_id', $user->id)->first() : null;
        if ($own) {
            $s = app(PartnerStats::class)->for($own);
            $dashboard['sections'][] = ['title' => 'Mes résultats de partenaire', 'icon' => 'bx bx-trending-up', 'tables' => [], 'cards' => [
                $this->card('Visites (30 jours)', (string) $s['visits_period'], 'venues de votre lien', 'bx bx-mouse', 'primary'),
                $this->card('Demandes', (string) $s['demandes'], 'devis, contacts, démos', 'bx bx-envelope', 'info'),
                $this->card('Ventes', (string) $s['ventes'], Materiel::money($s['chiffre']), 'bx bx-cart', 'success'),
                $this->card('Commissions à recevoir', Materiel::money($s['a_payer']), 'payées : '.Materiel::money($s['payees']), 'bx bx-wallet', 'warning', true),
            ]];
        }
        if ($user->can('partenaires.commissions')) {
            $due = (int) Commission::where('status', CommissionStatus::APayer->value)->sum('amount');
            if ($due > 0) {
                $dashboard['tasks'][] = $this->task('Commissions à payer', Materiel::money($due).' aux partenaires', Commission::where('status', CommissionStatus::APayer->value)->count(), 'bx bx-wallet', 'warning', 'partenaires.index');
            }
        }
    }

    /** Comptes et accès (users.view) : périmètre visible de l'acteur (User::visibleTo). */
    private function comptes(User $user, array &$dashboard): void
    {
        $visible = fn () => User::query()->visibleTo($user);

        $cards = [
            $this->card('Utilisateurs actifs', (string) $visible()->active()->count(), $visible()->count().' au total', 'bx bx-group', 'primary'),
            $this->card('Comptes désactivés', (string) $visible()->where('is_active', false)->count(), null, 'bx bx-lock-alt', 'secondary'),
            $this->card('Mots de passe temporaires', (string) $visible()->where('must_change_password', true)->count(), 'à changer à la connexion', 'bx bx-key', 'warning', true),
        ];

        $tables = [[
            'title' => 'DERNIÈRES CONNEXIONS', 'icon' => 'bx bx-log-in',
            'columns' => ['Utilisateur', 'Rôle', 'Dernière connexion', 'Statut'],
            'rows' => $visible()->with('roles')->whereNotNull('last_login_at')->orderByDesc('last_login_at')->limit(8)->get()->map(fn (User $u) => [
                $u->name, $u->roleName()?->label() ?? '—', $u->last_login_at->format('d/m/Y H:i'),
                $u->is_active ? ['badge' => 'Actif', 'class' => 'bg-success-subtle'] : ['badge' => 'Désactivé', 'class' => 'bg-secondary-subtle'],
            ])->all(),
        ]];

        $dashboard['sections'][] = ['title' => 'Comptes et accès', 'icon' => 'bx bx-user-circle', 'cards' => $cards, 'tables' => $tables];
    }

    /** Exploitation (exploitation.view) : sauvegarde, destinataire des notifications e-mail. */
    private function exploitation(array &$dashboard): void
    {
        $backup = $this->backupState();
        $recipient = $this->settings->notificationEmail();
        $mailer = (string) config('mail.default');
        $mailReal = ! in_array($mailer, ['log', 'array'], true);

        if (! $backup['ok']) {
            $dashboard['alerts'][] = ['class' => 'alert-danger', 'icon' => 'bx bx-data', 'title' => 'Sauvegarde.', 'text' => $backup['message'].' Lancez « php artisan backup:run » et vérifiez la tâche planifiée (CLAUDE.md §9).'];
        }

        $cards = [
            $this->card('Dernière sauvegarde', $backup['value'], $backup['sub'], 'bx bx-data', $backup['ok'] ? 'success' : 'danger'),
            $this->card('Notifications e-mail', $mailReal ? 'Envoi actif' : 'Non configuré', $recipient ? "vers {$recipient}" : 'aucun destinataire', 'bx bx-envelope', $mailReal && $recipient ? 'info' : 'warning', ! ($mailReal && $recipient)),
        ];

        $dashboard['sections'][] = ['title' => 'Exploitation', 'icon' => 'bx bx-server', 'cards' => $cards, 'tables' => []];
    }

    /** Supervision (superadmin) : comptes superadmin, activité par agent, journal d'audit. */
    private function supervision(array &$dashboard): void
    {
        $activeSuperadmins = User::role(RoleName::Superadmin->value)->active()->count();
        if ($activeSuperadmins < 2) {
            $dashboard['alerts'][] = ['class' => 'alert-warning', 'icon' => 'bx bx-error', 'title' => 'Un seul superadmin actif.', 'text' => 'Créez un second compte superadmin (un compte individuel par développeur) : le dernier superadmin actif est protégé et ne peut pas être désactivé.'];
        }

        $since = now()->subDays(30);
        $actions = ActivityLog::where('created_at', '>=', $since)->whereNotNull('user_id')->where('module', '!=', 'auth')
            ->selectRaw('user_id as uid, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'uid');

        $cards = [
            $this->card('Connexions aujourd\'hui', (string) ActivityLog::where('action', 'auth.login')->where('created_at', '>=', now()->startOfDay())->count(), null, 'bx bx-log-in-circle', 'dark'),
            $this->card('Actions journalisées', (string) ActivityLog::where('created_at', '>=', now()->startOfDay())->count(), 'aujourd\'hui', 'bx bx-list-check', 'primary'),
            $this->card('Superadmins actifs', (string) $activeSuperadmins, null, 'bx bx-shield-quarter', $activeSuperadmins < 2 ? 'warning' : 'success', $activeSuperadmins < 2),
        ];

        $tables = [
            [
                'title' => 'ACTIVITÉ PAR AGENT (30 JOURS)', 'icon' => 'bx bx-user-check',
                'columns' => ['Agent', 'Rôle', 'Actions', 'Dernière connexion'],
                'rows' => User::with('roles')->orderBy('name')->get()->map(fn (User $u) => [
                    $u->name, $u->roleName()?->label() ?? '—', (string) ($actions[$u->id] ?? 0), $u->last_login_at?->format('d/m/Y H:i') ?? 'jamais',
                ])->all(),
            ],
            [
                'title' => 'DERNIÈRES ACTIONS', 'icon' => 'bx bx-list-check',
                'columns' => ['Date', 'Auteur', 'Action'],
                'rows' => ActivityLog::latest('id')->limit(10)->get()->map(fn (ActivityLog $log) => [
                    $log->created_at->format('d/m/Y H:i'), $log->user_name ?? '—', $log->description,
                ])->all(),
            ],
        ];

        $dashboard['sections'][] = ['title' => 'Supervision', 'icon' => 'bx bx-show', 'cards' => $cards, 'tables' => $tables];
    }

    /**
     * État de la dernière sauvegarde (fichier le plus récent et dernier résultat de backup:verify).
     *
     * @return array{ok: bool, value: string, sub: string, message: string}
     */
    private function backupState(): array
    {
        $file = $this->backups->latest();
        if ($file === null) {
            return ['ok' => false, 'value' => 'Aucune', 'sub' => 'aucune sauvegarde trouvée', 'message' => 'Aucune sauvegarde de la base n\'a été trouvée.'];
        }

        $at = Carbon::createFromTimestamp(filemtime($file))->setTimezone(config('app.timezone'));
        $hours = (int) $at->diffInHours(now());
        $verification = Cache::get('backup.last_verification');
        $verified = is_array($verification) && $verification['file'] === basename($file);

        if ($hours > self::BACKUP_MAX_AGE_HOURS) {
            return ['ok' => false, 'value' => $at->format('d/m/Y H:i'), 'sub' => "il y a {$hours} h", 'message' => "La dernière sauvegarde date de {$hours} heures."];
        }
        if ($verified && ! $verification['ok']) {
            return ['ok' => false, 'value' => $at->format('d/m/Y H:i'), 'sub' => 'vérification échouée', 'message' => 'La dernière sauvegarde a échoué à la vérification : '.implode(' ', $verification['problems'])];
        }

        return ['ok' => true, 'value' => $at->format('d/m/Y H:i'), 'sub' => $verified ? 'vérifiée' : 'non encore vérifiée', 'message' => ''];
    }

    private function heading(User $user): string
    {
        return match ($user->roleName()) {
            RoleName::Superadmin => 'Supervision générale',
            RoleName::Administrateur => 'Administration du site',
            RoleName::Gestionnaire => 'Contenus du site',
            RoleName::Commercial => 'Demandes commerciales',
            default => 'Tableau de bord',
        };
    }

    /**
     * Accès rapides selon les permissions (cahier §7 « raccourcis de gestion ») ; les deux premiers s'affichent aussi à
     * droite du fil d'Ariane.
     *
     * @return list<array<string, mixed>>
     */
    private function quick(User $user): array
    {
        $candidates = [
            ['partenaire.espace', 'partenaires.dashboard', 'Mon espace partenaire', 'bx bx-trending-up'],
            ['kit.index', 'partenaires.kit', 'Kit de publication', 'bx bx-share'],
            ['demandes.index', 'demandes.view', 'Demandes', 'bx bx-envelope'],
            ['logiciels.create', 'logiciels.create', 'Nouveau logiciel', 'bx bx-code-block'],
            ['materiels.create', 'materiels.create', 'Nouveau matériel', 'bx bx-laptop'],
            ['articles.create', 'articles.create', 'Rédiger un article', 'bx bx-pencil'],
            ['realisations.create', 'realisations.create', 'Nouvelle réalisation', 'bx bx-briefcase-alt-2'],
            ['medias.index', 'medias.view', 'Médias', 'bx bx-images'],
            ['pages.edit', 'pages.update', 'Pages et paramètres', 'bx bx-file'],
            ['users.index', 'users.view', 'Utilisateurs', 'bx bx-user-circle'],
            ['audit.index', 'audit.view', 'Audit', 'bx bx-list-check'],
            ['system.index', 'system.view', 'Système', 'bx bx-server'],
        ];

        $quick = [];
        foreach ($candidates as [$route, $permission, $label, $icon]) {
            if (Route::has($route) && $user->can($permission)) {
                $quick[] = ['label' => $label, 'icon' => $icon, 'route' => $route, 'params' => []];
            }
        }

        return $quick;
    }

    /**
     * @return array<string, mixed>
     */
    private function task(string $label, string $hint, int $count, string $icon, string $color, string $route, array $params = []): array
    {
        return compact('label', 'hint', 'count', 'icon', 'color', 'route', 'params');
    }

    /**
     * @return array<string, mixed>
     */
    private function card(string $label, string $value, ?string $sub, string $icon, string $color, bool $darkText = false): array
    {
        return ['label' => $label, 'value' => $value, 'sub' => $sub, 'icon' => $icon, 'color' => $color, 'darkText' => $darkText];
    }
}
