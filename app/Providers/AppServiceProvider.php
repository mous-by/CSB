<?php

namespace App\Providers;

use App\Http\Controllers\Client\ClientAreaSections;
use App\Models\Abonne;
use App\Models\ApiToken;
use App\Models\AppSetting;
use App\Models\Article;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\Commission;
use App\Models\Demande;
use App\Models\DemandeNote;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Partenaire;
use App\Models\Permission;
use App\Models\Prestation;
use App\Models\Realisation;
use App\Models\Redirection;
use App\Models\StockSync;
use App\Models\Temoignage;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Boutique\CartService;
use App\Services\Users\SuperadminBootstrapper;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SuperadminBootstrapper::class);
        $this->app->singleton(SiteSettings::class);
        $this->app->singleton(ClientAreaSections::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Le superadmin contourne l'autorisation (Gate::before), pas la logique métier ni l'audit (CLAUDE.md §4, D16).
        Gate::before(fn ($user, string $ability) => $user instanceof User && $user->isSuperadmin() ? true : null);

        // D14 : 8 caractères au moins, lettres et chiffres.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Proxy de confiance (config/site.php) : https et nom d'hôte du tunnel repris dans les adresses générées.
        if ($proxies = config('site.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Superadmins créés automatiquement s'ils n'existent pas (D27) : au lancement du serveur de développement et après
        // chaque migration (déploiement). La première visite de la page de connexion est le filet pour l'hébergement Web.
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command === 'serve') {
                app(SuperadminBootstrapper::class)->ensure();
            }
        });
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->exitCode === 0 && in_array($event->command, ['migrate', 'migrate:fresh', 'migrate:refresh', 'permissions:sync'], true)) {
                app(SuperadminBootstrapper::class)->ensure();
            }
        });

        // Formulaires publics (cahier §10 « protection anti-spam ») : 5 envois par minute et 30 par jour par adresse IP.
        RateLimiter::for('demandes', fn (Request $request) => [
            Limit::perMinute(5)->by('demandes-min|'.$request->ip()),
            Limit::perDay(30)->by('demandes-jour|'.$request->ip()),
        ]);

        // Pagination du site public au style Bootstrap 5 (l'administration utilise DataTables).
        Paginator::useBootstrapFive();

        // Paramètres du site (coordonnées, slogan…) disponibles dans toutes les vues du site public et dans les e-mails.
        View::composer(['layouts.site', 'site.*', 'mail.*', 'client.*'], fn ($view) => $view->with('site', app(SiteSettings::class)));

        View::composer('site.partials.footer', fn ($view) => $view->with('footerServices', rescue(fn () => Prestation::published()->ordered()->limit(6)->get(['title', 'slug']), collect(), false)));

        // Sous-menu « Nos solutions » de l'en-tête : logiciels du catalogue (requête unique par page, mise en cache 10 min).
        // Le cache ne contient que des tableaux : Laravel 13 refuse de désérialiser les objets (modèles, collections)
        // depuis le cache (cache.serializable_classes), ce qui faisait échouer toutes les pages après la première.
        View::composer('site.partials.header', function ($view) {
            $view->with('cartCount', rescue(fn () => app(CartService::class)->count(), 0, false));
            $view->with('navSolutions', collect(rescue(fn () => Cache::remember('site.nav_solutions.v2', 600, fn () => Logiciel::with(['logo', 'category'])->inCatalogue()->ordered()->limit(8)->get()
                ->map(fn (Logiciel $l) => ['slug' => $l->slug, 'name' => $l->name, 'subtitle' => $l->tagline ?? $l->category?->name, 'logo' => $l->logo?->thumbUrl()])->all()), [], false)));
        });

        // Tableau de bord de l'espace client : blocs « Commandes » et « Tickets » (en cours / total).
        $this->app->make(ClientAreaSections::class)->register(function (Client $client) {
            return ['title' => 'Commandes', 'icon' => 'bx-cart', 'route' => 'client.commandes', 'count' => Commande::where('client_id', $client->id)->count(), 'open' => Commande::where('client_id', $client->id)->open()->count(), 'label' => 'en cours'];
        });
        $this->app->make(ClientAreaSections::class)->register(function (Client $client) {
            $total = Ticket::where('client_id', $client->id)->count();

            return ['title' => 'Tickets', 'icon' => 'bx-support', 'route' => 'client.tickets', 'count' => $total, 'open' => Ticket::where('client_id', $client->id)->open()->count(), 'label' => 'en cours'];
        });

        // Alias morph stables : ils s'écrivent dans audit_logs et model_has_roles. Chaque module y ajoute ses modèles
        // depuis son propre fournisseur ou ici (CLAUDE.md §6).
        Relation::morphMap([
            'user' => User::class,
            'app_setting' => AppSetting::class,
            'abonne' => Abonne::class,
            'api_token' => ApiToken::class,
            'stock_sync' => StockSync::class,
            'media' => Media::class,
            'category' => Category::class,
            'client' => Client::class,
            'client_document' => ClientDocument::class,
            'commande' => Commande::class,
            'commission' => Commission::class,
            'partenaire' => Partenaire::class,
            'commande_ligne' => CommandeLigne::class,
            'logiciel' => Logiciel::class,
            'article' => Article::class,
            'demande' => Demande::class,
            'demande_note' => DemandeNote::class,
            'materiel' => Materiel::class,
            'prestation' => Prestation::class,
            'realisation' => Realisation::class,
            'redirection' => Redirection::class,
            'temoignage' => Temoignage::class,
            'ticket' => Ticket::class,
            'ticket_message' => TicketMessage::class,
            'permission' => Permission::class,
            'role' => Role::class,
        ]);
    }
}
