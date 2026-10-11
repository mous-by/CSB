<?php

namespace Database\Seeders;

use App\Enums\CommandeStatus;
use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Enums\NeedType;
use App\Enums\TicketStatus;
use App\Models\Abonne;
use App\Models\Article;
use App\Models\Category;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Demande;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Temoignage;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Boutique\CommandeService;
use App\Services\Tickets\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Données de TEST (demande de CSB, 2026-10-11 : « donne des données partout pour me faciliter le test ») : prix et
 * promotions du matériel, tarifs des logiciels, actualités, témoignages, clients, demandes, tickets, commandes, abonnés.
 * Les prix, témoignages, clients et demandes sont FICTIFS : à vider ou remplacer avant la mise en ligne.
 * Jamais en production (refus). Idempotent : relancer ne crée pas de doublon.
 *
 *   php artisan db:seed --class=ContenuCsbSeeder   # contenu réel d'abord
 *   php artisan db:seed --class=DonneesTestSeeder
 */
class DonneesTestSeeder extends Seeder
{
    /** Domaine des adresses de test (permet de les retrouver et de les supprimer). */
    public const EMAIL_DOMAIN = 'test.csb.ml';

    public function run(TicketService $tickets): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DonneesTestSeeder : données fictives, interdites en production.');
        }

        // Aucun e-mail réel pendant l'amorçage (notifications des tickets).
        Mail::fake();

        $this->prices();
        $this->articles();
        $this->temoignages();
        $password = $this->clients();
        $this->demandes();
        $this->tickets($tickets);
        $this->commandes();
        $this->abonnes();

        $this->command?->info('Données de test créées. Clients : *@'.self::EMAIL_DOMAIN.($password ? ", mot de passe : {$password}" : ' (mot de passe inchangé)'));
    }

    private function prices(): void
    {
        // slug => [prix, prix promo ou null, conditions]
        $materiels = [
            'ordinateur-portable-hp-elitebook-840-g6' => [285000, 249000, 'Garantie 6 mois, Windows 11 Pro installé'],
            'ordinateurs-portables-hp-serie-g3' => [165000, null, 'Prix à l\'unité, garantie 3 mois'],
            'ordinateur-de-bureau-dell-optiplex-7040-avec-ecran' => [210000, 185000, 'Unité centrale + écran 22", clavier et souris'],
            'imprimante-multifonction-jet-dencre' => [95000, null, 'Cartouches de départ incluses'],
            'imprimante-thermique-de-tickets' => [65000, 55000, 'Rouleau de départ inclus, compatible JÀGO DÁNAYA'],
            'clavier-et-souris-a4tech-km-720u' => [12500, null, 'Filaire USB'],
        ];
        // Références (code article du logiciel de stock) et stocks de test, pour essayer la synchronisation.
        $stock = [
            'ordinateur-portable-hp-elitebook-840-g6' => ['HP-840-G6', 4], 'ordinateurs-portables-hp-serie-g3' => ['HP-G3', 10],
            'ordinateur-de-bureau-dell-optiplex-7040-avec-ecran' => ['DELL-7040', 3], 'imprimante-multifonction-jet-dencre' => ['IMP-JET', 2],
            'imprimante-thermique-de-tickets' => ['IMP-THERM', 6], 'clavier-et-souris-a4tech-km-720u' => ['A4-KM720', 25],
        ];
        foreach ($stock as $slug => [$reference, $quantity]) {
            Materiel::where('slug', $slug)->whereNull('reference')->first()?->update(['reference' => $reference, 'stock_quantity' => $quantity]);
        }

        foreach ($materiels as $slug => [$price, $promo, $conditions]) {
            $materiel = Materiel::where('slug', $slug)->first();
            if ($materiel && $materiel->price === null) {
                $materiel->update([
                    'price' => $price, 'promo_price' => $promo, 'promo_ends_on' => $promo ? today()->addDays(30) : null, 'price_conditions' => $conditions,
                ]);
            }
        }

        $logiciels = [
            'kalannet' => 'À partir de 150 000 FCFA par an et par établissement',
            'jago-danaya' => 'À partir de 75 000 FCFA (licence) + 10 000 FCFA / mois',
            'sirali' => 'À partir de 250 000 FCFA (installation) + abonnement mensuel',
            'orgest' => 'À partir de 200 000 FCFA (licence)',
            'ateliko' => 'À partir de 50 000 FCFA (licence)',
            'solutions-sur-mesure' => 'Sur devis, selon le cahier des charges',
        ];
        foreach ($logiciels as $slug => $text) {
            $logiciel = Logiciel::where('slug', $slug)->first();
            if ($logiciel && ! $logiciel->price_text) {
                $logiciel->update(['price_text' => $text]);
            }
        }
    }

    private function articles(): void
    {
        $category = fn (string $name) => Category::where('type', 'article')->where('name', $name)->value('id');
        $cover = fn (string $file) => Media::where('original_name', $file)->value('id');
        $author = User::orderBy('id')->value('id');

        $items = [
            ['KalanNet : les bulletins de fin de trimestre en un clic', 'Actualités', 'kalannet-banniere.jpg', 12,
                'Les écoles équipées de KalanNet génèrent désormais les bulletins de toute une classe en une seule opération.',
                "Fin de trimestre rime souvent avec nuits blanches pour les directions d'école. Avec **KalanNet**, les notes saisies par les enseignants alimentent directement les bulletins.\n\n## Ce qui change\n\n- calcul automatique des moyennes et des rangs ;\n- bulletins de toute une classe en PDF, prêts à imprimer ;\n- consultation par les parents depuis leur téléphone.\n\nVous souhaitez une démonstration ? Contactez-nous."],
            ['5 conseils pour faire durer votre ordinateur portable', 'Conseils', 'cat-portables.jpg', 6,
                'Batterie, poussière, mises à jour : les bons gestes pour garder un portable rapide et fiable plusieurs années.',
                "Un ordinateur bien entretenu dure deux à trois fois plus longtemps.\n\n1. **Nettoyez les grilles d'aération** tous les trois mois : la poussière fait chauffer le processeur.\n2. **Ne laissez pas la batterie à 0 %** : rechargez-la avant 20 %.\n3. **Faites les mises à jour** de Windows et de l'antivirus.\n4. **Sauvegardez vos fichiers** sur un disque externe ou dans le cloud.\n5. **Utilisez une prise protégée** (onduleur ou parafoudre) : les coupures de courant abîment l'alimentation.\n\nUn souci ? Notre atelier réparation et maintenance vous accueille à Ségou."],
            ['Promotions de rentrée sur le matériel informatique', 'Annonces', 'cat-bureau.jpg', 2,
                'Ordinateurs de bureau, portables et imprimantes thermiques à prix réduit pendant un mois.',
                "Pour la rentrée, CSB baisse ses prix sur une sélection de matériel : ordinateurs portables HP EliteBook, ensembles Dell OptiPlex avec écran et imprimantes thermiques de tickets.\n\nLes prix barrés sont visibles dans notre catalogue **Matériel informatique**. Offre valable dans la limite des stocks disponibles."],
        ];
        foreach ($items as [$title, $categoryName, $file, $daysAgo, $excerpt, $body]) {
            $slug = Str::slug($title);
            if (Article::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }
            Article::create([
                'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'body' => $body, 'category_id' => $category($categoryName),
                'cover_media_id' => $cover($file), 'author_id' => $author, 'is_published' => true, 'published_at' => now()->subDays($daysAgo),
                // Déjà « envoyée » : le seeder ne déclenche pas la newsletter.
            ])->forceFill(['newsletter_sent_at' => now()])->saveQuietly();
        }
    }

    private function temoignages(): void
    {
        $items = [
            ['Aminata Coulibaly', 'Directrice des études', 'Groupe scolaire (test)', 'Depuis KalanNet, les bulletins sortent le jour même et les parents suivent les notes sur leur téléphone. L\'équipe de CSB nous a formés et reste joignable.'],
            ['Oumar Traoré', 'Gérant', 'Boutique de quincaillerie (test)', 'JÀGO DÁNAYA m\'a permis de suivre le stock de mes deux boutiques à distance. Je sais chaque soir ce qui a été vendu.'],
            ['Seydou Diarra', 'Responsable administratif', 'Compagnie de transport (test)', 'La vente des billets et l\'envoi des colis sont enfin tracés. Réactivité et sérieux : je recommande CSB.'],
        ];
        foreach ($items as $order => [$name, $title, $organization, $content]) {
            Temoignage::firstOrCreate(['author_name' => $name], [
                'author_title' => $title, 'organization' => $organization, 'content' => $content, 'sort_order' => $order, 'is_published' => true,
            ]);
        }
    }

    /** Clients de test ; renvoie le mot de passe généré (null si les comptes existaient déjà). */
    private function clients(): ?string
    {
        $password = null;
        $items = [
            ['Awa Diakité', 'École Les Étoiles (test)', 'ecole', '+22370000001', 'Ségou'],
            ['Moussa Keïta', 'Quincaillerie Keïta (test)', 'quincaillerie', '+22370000002', 'Bamako'],
            ['Fatoumata Sangaré', null, 'fatoumata', '+22370000003', 'Ségou'],
        ];
        foreach ($items as [$name, $organization, $local, $phone, $city]) {
            $email = $local.'@'.self::EMAIL_DOMAIN;
            if (Client::withTrashed()->where('email', $email)->exists()) {
                continue;
            }
            $password ??= 'Test-'.Str::upper(Str::random(6)).'-'.random_int(10, 99);
            Client::create([
                'name' => $name, 'organization' => $organization, 'email' => $email, 'phone' => $phone, 'city' => $city,
                'password' => $password, 'is_active' => true, 'email_verified_at' => now(),
            ]);
        }

        return $password;
    }

    private function demandes(): void
    {
        if (Demande::where('email', 'like', '%@'.self::EMAIL_DOMAIN)->exists()) {
            return;
        }
        $client = fn (string $local) => Client::where('email', $local.'@'.self::EMAIL_DOMAIN)->value('id');
        $kalannet = Logiciel::where('slug', 'kalannet')->first();
        $hp = Materiel::where('slug', 'ordinateur-portable-hp-elitebook-840-g6')->first();
        $agent = User::orderBy('id')->value('id');

        $items = [
            ['type' => DemandeType::Devis, 'status' => DemandeStatus::Nouvelle, 'name' => 'Awa Diakité', 'organization' => 'École Les Étoiles (test)', 'email' => 'ecole@'.self::EMAIL_DOMAIN, 'phone' => '+22370000001', 'client_id' => $client('ecole'),
                'need_type' => NeedType::Logiciel, 'subject' => 'Logiciel de gestion scolaire pour 450 élèves', 'message' => 'Nous souhaitons équiper notre école (primaire et second cycle) pour la rentrée : inscriptions, notes, bulletins et paiements.', 'budget' => '500 000 à 1 000 000 FCFA', 'deadline' => 'Avant la rentrée', 'days' => 0],
            ['type' => DemandeType::Demonstration, 'status' => DemandeStatus::EnCours, 'name' => 'Ibrahim Touré', 'organization' => 'Lycée privé (test)', 'email' => 'ibrahim@'.self::EMAIL_DOMAIN, 'phone' => '+22370000004',
                'subject_type' => $kalannet?->getMorphClass(), 'subject_id' => $kalannet?->id, 'subject_label' => $kalannet?->name, 'message' => 'Une démonstration de KalanNet pour le conseil d\'administration.', 'availability' => 'Mardi ou jeudi matin', 'days' => 2, 'assigned_to' => $agent],
            ['type' => DemandeType::Disponibilite, 'status' => DemandeStatus::DevisEnvoye, 'name' => 'Moussa Keïta', 'organization' => 'Quincaillerie Keïta (test)', 'email' => 'quincaillerie@'.self::EMAIL_DOMAIN, 'phone' => '+22370000002', 'client_id' => $client('quincaillerie'),
                'subject_type' => $hp?->getMorphClass(), 'subject_id' => $hp?->id, 'subject_label' => $hp?->designation, 'message' => 'Avez-vous 3 unités en stock ? Livraison possible à Bamako ?', 'days' => 5, 'assigned_to' => $agent],
            ['type' => DemandeType::Contact, 'status' => DemandeStatus::Gagnee, 'name' => 'Fatoumata Sangaré', 'email' => 'fatoumata@'.self::EMAIL_DOMAIN, 'phone' => '+22370000003', 'client_id' => $client('fatoumata'),
                'subject' => 'Réparation d\'un ordinateur', 'message' => 'Mon ordinateur portable ne s\'allume plus après une coupure de courant.', 'days' => 9, 'assigned_to' => $agent],
            ['type' => DemandeType::Devis, 'status' => DemandeStatus::Perdue, 'name' => 'Bakary Samaké', 'organization' => 'ONG locale (test)', 'email' => 'bakary@'.self::EMAIL_DOMAIN, 'phone' => '+22370000005',
                'need_type' => NeedType::SurMesure, 'subject' => 'Application de suivi des bénéficiaires', 'message' => 'Suivi des bénéficiaires et des distributions dans trois cercles.', 'budget' => 'Moins de 500 000 FCFA', 'days' => 20, 'closed_reason' => 'Budget insuffisant (test)'],
        ];
        foreach ($items as $item) {
            $days = $item['days'];
            unset($item['days']);
            $demande = Demande::create($item + ['consent_at' => now()->subDays($days), 'source_url' => url('/'), 'ip_address' => '127.0.0.1', 'status_changed_at' => now()->subDays($days)]);
            $demande->forceFill(['created_at' => now()->subDays($days)->subHours(3)])->saveQuietly();
        }
    }

    private function tickets(TicketService $service): void
    {
        $client = Client::where('email', 'ecole@'.self::EMAIL_DOMAIN)->first();
        $other = Client::where('email', 'fatoumata@'.self::EMAIL_DOMAIN)->first();
        $agent = User::orderBy('id')->first();
        if (! $client || ! $other || ! $agent || Ticket::whereIn('client_id', [$client->id, $other->id])->exists()) {
            return;
        }

        $first = $service->open($client, ['subject' => 'Les bulletins ne s\'impriment pas en couleur', 'category' => 'logiciel', 'priority' => 'normale', 'device' => 'KalanNet, imprimante HP', 'message' => 'Depuis hier, les bulletins de la classe de 6e A sortent en noir et blanc alors que l\'imprimante est en couleur.']);
        $service->teamReply($first, $agent, 'Bonjour, pouvez-vous vérifier dans les préférences de l\'imprimante que l\'option « Niveaux de gris » est désactivée ? Sinon, nous passons à l\'école demain matin.', false, TicketStatus::AttenteClient);
        $service->teamReply($first, $agent, 'Vérifier aussi le pilote installé (test).', true);

        $second = $service->open($other, ['subject' => 'Ordinateur très lent au démarrage', 'category' => 'reparation', 'priority' => 'haute', 'device' => 'HP EliteBook 840 G6', 'message' => 'Le démarrage prend plus de 5 minutes et le ventilateur fait beaucoup de bruit.']);
        $service->teamReply($second, $agent, 'Déposez l\'ordinateur à l\'atelier : nettoyage, contrôle du disque et de la mémoire. Diagnostic gratuit.', false, TicketStatus::EnCours);
    }

    private function commandes(): void
    {
        $client = Client::where('email', 'quincaillerie@'.self::EMAIL_DOMAIN)->first();
        if (! $client || Commande::where('client_id', $client->id)->exists()) {
            return;
        }
        $service = app(CommandeService::class);
        $line = function (string $slug, int $quantity) {
            $m = Materiel::where('slug', $slug)->first();

            return $m && $m->price !== null ? ['materiel' => $m, 'quantity' => $quantity, 'unit_price' => (int) $m->currentPrice(), 'normal_price' => (int) $m->price, 'line_total' => (int) $m->currentPrice() * $quantity] : null;
        };

        $first = $service->place(
            ['name' => $client->name, 'organization' => $client->organization, 'email' => $client->email, 'phone' => $client->phone, 'delivery_method' => 'livraison', 'address' => 'Marché central (test)', 'city' => 'Bamako', 'payment_method' => 'orange_money', 'notes' => 'Livrer avant midi (test).'],
            collect([$line('imprimante-thermique-de-tickets', 2), $line('clavier-et-souris-a4tech-km-720u', 3)])->filter(), $client, '127.0.0.1');
        $agent = User::orderBy('id')->first();
        if ($agent) {
            $service->changeStatus($first, CommandeStatus::Payee, $agent, 'OM-TEST-0001');
        }

        $service->place(
            ['name' => 'Mariam Coulibaly', 'email' => 'mariam@'.self::EMAIL_DOMAIN, 'phone' => '+22370000006', 'delivery_method' => 'retrait', 'payment_method' => 'especes'],
            collect([$line('ordinateur-portable-hp-elitebook-840-g6', 1)])->filter(), null, '127.0.0.1');
    }

    private function abonnes(): void
    {
        foreach (['ecole', 'quincaillerie', 'lecteur'] as $local) {
            Abonne::firstOrCreate(['email' => $local.'@'.self::EMAIL_DOMAIN], ['source' => 'test', 'subscribed_at' => now()->subDays(random_int(1, 30))]);
        }
    }
}
