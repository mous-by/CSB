<?php

namespace Database\Seeders;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Enums\MaterielCondition;
use App\Models\Category;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Realisation;
use App\Services\Medias\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Contenu réel de CSB (fourni le 2026-10-11 : maquette de l'accueil, logo, captures des logiciels, documentations
 * utilisateur de KalanNet, JÀGO DÁNAYA et ATELIKO, photos des produits et des projets). Utilisable en production et
 * idempotent : un élément déjà présent (même adresse) n'est jamais modifié, pour ne pas écraser une correction faite dans
 * l'administration. Les fonctionnalités reprennent les documentations ; ce qui n'y figure pas est laissé vide plutôt
 * qu'inventé (cahier §5.1, §5.4). Images : database/seeders/assets/csb/.
 */
class ContenuCsbSeeder extends Seeder
{
    private const ASSETS = __DIR__.'/assets/csb/';

    /** @var array<string, int> image déjà importée => identifiant (une image n'est importée qu'une fois) */
    private array $imported = [];

    public function run(MediaService $medias): void
    {
        DB::transaction(function () use ($medias) {
            $this->categories($medias);
            $this->logiciels($medias);
            $this->materiels($medias);
            $this->realisations($medias);
        });
    }

    private function categories(MediaService $medias): void
    {
        $materiel = [
            'Ordinateurs portables' => ['Neuf et occasion', 'cat-portables.jpg'],
            'Ordinateurs de bureau' => ['Configuration sur mesure', 'cat-bureau.jpg'],
            'Imprimantes' => ['HP, Canon, Brother…', 'cat-imprimantes.jpg'],
            'Accessoires' => ['Claviers, souris, casques, câbles…', 'cat-accessoires.jpg'],
            'Réseau et composants' => ['Routeurs, switchs, câblage, pièces', null],
        ];

        foreach ($materiel as $name => [$description, $image]) {
            $category = Category::ofType(CategoryType::Materiel)->firstOrCreate(['name' => $name], ['type' => CategoryType::Materiel, 'sort_order' => 0]);
            $category->fill([
                'description' => $category->description ?: $description,
                'media_id' => $category->media_id ?: ($image ? $this->media($medias, $image, $name, 'Matériel') : null),
            ])->save();
        }
    }

    private function logiciels(MediaService $medias): void
    {
        $category = fn (string $name) => Category::ofType(CategoryType::Logiciel)->firstOrCreate(['name' => $name], ['type' => CategoryType::Logiciel])->id;

        $items = [
            [
                'name' => 'KalanNet', 'slug' => 'kalannet', 'tagline' => 'Gestion scolaire web et mobile', 'sort_order' => 1, 'featured' => true,
                'category' => 'Éducation et gestion scolaire', 'platforms' => ['web', 'android'],
                'summary' => 'Inscriptions, présences, notes, bulletins, paiements, emplois du temps et communication avec les parents, pour toute l\'école.',
                'problem' => 'Les écoles gèrent inscriptions, notes, présences et paiements sur papier ou dans des fichiers dispersés : les bulletins prennent du temps, les impayés se perdent et les parents sont mal informés.',
                'features' => [
                    'Inscriptions individuelles ou par groupe (modèle Excel) et réinscriptions en un clic',
                    'Dossier complet de chaque élève : parcours, paiements, reçus',
                    'Cartes scolaires imprimables avec QR code',
                    'Espace parents : montants à payer, retards, reçus de leurs seuls enfants',
                    'Enseignants : émargements, cahier de présence, calcul et paiement des salaires',
                    'Classes, matières, programmes officiels et emploi du temps',
                    'Contrôles, évaluations et bulletins',
                    'Annonces et suivi des finances de l\'école',
                    'Plusieurs écoles avec le même compte, droits d\'accès par utilisateur',
                    'Assistant vocal KalanBot : « inscris Sékou Traoré en 7e année »',
                ],
                'logo' => 'kalannet-logo.png', 'gallery' => ['kalannet-banniere.jpg', 'kalannet-mobile-accueil.jpg', 'kalannet-mobile-tableau.jpg', 'kalannet-connexion.jpg'],
            ],
            [
                'name' => 'JÀGO DÁNAYA', 'slug' => 'jago-danaya', 'tagline' => 'Gestion de stock et commerciale', 'sort_order' => 2, 'featured' => true,
                'category' => 'Commerce, vente et gestion de stock', 'platforms' => ['web', 'android'],
                'summary' => 'Achats, ventes, stock, rapports, multi-magasins : la gestion complète de votre activité commerciale.',
                'problem' => 'Stock mal suivi, ventes non comptabilisées, pertes invisibles : sans outil, le commerçant ne sait pas ce qu\'il gagne réellement.',
                'features' => [
                    'Point de vente : ventes rapides, paiement en espèces ou mobile money',
                    'Reçus imprimés ou envoyés par e-mail ou SMS',
                    'Suivi des ventes en temps réel',
                    'Inventaire, catégories et mouvements de stock (entrées, sorties)',
                    'Alertes quand le stock est bas',
                    'Devis et factures professionnels, envoyés par e-mail ou WhatsApp',
                    'Plusieurs boutiques dans un même compte',
                    'Rapports et historique',
                    'Sauvegarde et exportation des données',
                ],
                'logo' => 'jago-logo.png', 'gallery' => ['jago-banniere.jpg', 'jago-ventes-caisse.jpg', 'jago-produits-stock.jpg', 'jago-rapports.jpg', 'jago-multi-boutiques.jpg', 'jago-tableau-de-bord.jpg', 'jago-liste-produits.jpg'],
            ],
            [
                'name' => 'SIRALI', 'slug' => 'sirali', 'tagline' => 'Gestion du transport et des réservations', 'sort_order' => 3, 'featured' => true,
                'category' => 'Transport, réservation et logistique', 'platforms' => ['web', 'android'],
                'summary' => 'Gestion des billets et des colis, réservations en ligne, suivi des voyages et rapports pour les compagnies de transport.',
                'problem' => 'Billets vendus sur carnet, colis sans suivi, recettes difficiles à contrôler d\'une gare à l\'autre.',
                'features' => [
                    'Vente et gestion des billets',
                    'Enregistrement et suivi des colis',
                    'Réservation en ligne depuis le site de la compagnie',
                    'Suivi des voyages',
                    'Rapports d\'activité',
                ],
                'logo' => 'sirali-logo.png', 'gallery' => ['sirali-site.jpg', 'sirali-reservation.jpg', 'sirali-colis.jpg', 'sirali-gare.jpg'],
            ],
            [
                'name' => 'Solutions sur mesure', 'slug' => 'solutions-sur-mesure', 'tagline' => 'Pour entreprises et organisations', 'sort_order' => 4, 'featured' => true,
                'category' => 'Applications web et mobiles sur mesure', 'platforms' => ['web', 'android'],
                'summary' => 'Applications adaptées à vos besoins spécifiques : santé, entreprise, association, etc.',
                'problem' => 'Votre activité a des besoins qu\'aucun logiciel du marché ne couvre vraiment.',
                'features' => [],
                'logo' => null, 'gallery' => ['sur-mesure.jpg'], 'status' => LogicielStatus::SurDemande,
            ],
            [
                'name' => 'ORGEST', 'slug' => 'orgest', 'tagline' => 'Gestion d\'achat et de vente d\'or', 'sort_order' => 5, 'featured' => false,
                'category' => 'Gestion d\'entreprise et solutions métiers', 'platforms' => ['web'],
                'summary' => 'Achats et ventes d\'or, stock, trésorerie et clients pour les comptoirs d\'achat et de vente d\'or.',
                'problem' => 'Barèmes de carats calculés à la main, factures papier, stock d\'or et trésorerie difficiles à suivre.',
                'features' => ['Achats d\'or', 'Ventes d\'or', 'Stock d\'or', 'Trésorerie', 'Fichier clients'],
                'logo' => 'orgest-logo.png', 'gallery' => ['orgest-achats.jpg', 'orgest-ventes.jpg', 'orgest-stock.jpg', 'orgest-tresorerie.jpg'],
            ],
            [
                'name' => 'ATELIKO', 'slug' => 'ateliko', 'tagline' => 'Gestion d\'atelier de couture', 'sort_order' => 6, 'featured' => false,
                'category' => 'Gestion d\'entreprise et solutions métiers', 'platforms' => ['web'],
                'summary' => 'Clients et mesures, albums de modèles, travail confié aux tailleurs, rendez-vous et paiements de l\'atelier.',
                'problem' => 'Mesures notées dans des cahiers, rendez-vous oubliés, impayés des clients et paie des tailleurs mal suivis.',
                'features' => [
                    'Fiche client avec photo et mesures',
                    'Albums de modèles déjà cousus',
                    'Affectation des habits à coudre aux tailleurs',
                    'Rendez-vous de retrait',
                    'Paiements des clients et paie des tailleurs',
                    'Comptes et permissions pour chaque employé',
                ],
                'logo' => 'ateliko-logo.png', 'gallery' => ['ateliko-tableau.jpg', 'ateliko-clients.jpg', 'ateliko-albums.jpg', 'ateliko-connexion.jpg'],
            ],
        ];

        foreach ($items as $item) {
            if (Logiciel::withTrashed()->where('slug', $item['slug'])->exists()) {
                continue;
            }

            $logiciel = Logiciel::create([
                'name' => $item['name'], 'slug' => $item['slug'], 'tagline' => $item['tagline'], 'summary' => $item['summary'],
                'problem' => $item['problem'], 'category_id' => $category($item['category']), 'features' => $item['features'],
                'platforms' => $item['platforms'], 'status' => $item['status'] ?? LogicielStatus::Disponible, 'is_published' => true,
                'is_featured' => $item['featured'], 'sort_order' => $item['sort_order'],
                'logo_media_id' => $item['logo'] ? $this->media($medias, $item['logo'], 'Logo '.$item['name'], 'Logiciels') : null,
            ]);
            $medias->syncGallery($logiciel, array_map(fn ($file) => $this->media($medias, $file, $item['name'].' — aperçu', 'Logiciels'), $item['gallery']));
        }
    }

    private function materiels(MediaService $medias): void
    {
        $category = fn (string $name) => Category::ofType(CategoryType::Materiel)->where('name', $name)->value('id');

        $items = [
            ['Ordinateur portable HP EliteBook 840 G6', 'Ordinateurs portables', 'HP', 'EliteBook 840 G6', MaterielCondition::Neuf, [['Processeur', 'Intel Core i5 8e génération'], ['Mémoire', '32 Go'], ['Stockage', 'SSD 1 To']], ['mat-hp-840-g6.jpg']],
            ['Ordinateurs portables HP (série G3)', 'Ordinateurs portables', 'HP', null, MaterielCondition::Occasion, [], ['mat-hp-g3.jpg']],
            ['Ordinateur de bureau Dell OptiPlex 7040 avec écran', 'Ordinateurs de bureau', 'Dell', 'OptiPlex 7040', MaterielCondition::Occasion, [['Livré avec', 'Écran, clavier et souris']], ['cat-bureau.jpg']],
            ['Imprimante multifonction jet d\'encre', 'Imprimantes', null, null, MaterielCondition::Neuf, [['Fonctions', 'Impression, copie, numérisation']], ['cat-imprimantes.jpg']],
            ['Imprimante thermique de tickets', 'Imprimantes', null, null, MaterielCondition::Neuf, [['Usage', 'Reçus de caisse (JÀGO DÁNAYA, point de vente)']], ['mat-imprimante-thermique.jpg', 'mat-imprimante-thermique-2.jpg']],
            ['Clavier et souris A4Tech KM-720U', 'Accessoires', 'A4Tech', 'KM-720U', MaterielCondition::Neuf, [['Connexion', 'USB']], ['cat-accessoires.jpg']],
        ];

        foreach ($items as $order => [$designation, $categoryName, $brand, $model, $condition, $specs, $images]) {
            $slug = str($designation)->slug()->toString();
            if (Materiel::withTrashed()->where('slug', $slug)->exists() || ! $category($categoryName)) {
                continue;
            }

            $ids = array_map(fn ($file) => $this->media($medias, $file, $designation, 'Matériel'), $images);
            $materiel = Materiel::create([
                'designation' => $designation, 'slug' => $slug, 'category_id' => $category($categoryName), 'brand' => $brand, 'model' => $model,
                'specs' => $specs, 'condition' => $condition, 'availability' => Availability::SurDemande, 'main_media_id' => array_shift($ids),
                'is_published' => true, 'sort_order' => $order,
            ]);
            $medias->syncGallery($materiel, $ids);
        }
    }

    private function realisations(MediaService $medias): void
    {
        $logiciel = fn (?string $slug) => $slug ? Logiciel::where('slug', $slug)->value('id') : null;

        $items = [
            ['KalanNet', 'kalannet-gestion-scolaire', 'Gestion scolaire', 'Logiciel de gestion scolaire déployé dans des établissements : inscriptions, notes, bulletins, paiements et espace parents.', 'kalannet', ['real-kalannet.jpg']],
            ['JÀGO DÁNAYA', 'jago-danaya-gestion-commerciale', 'Gestion commerciale', 'Gestion des ventes, du stock et de plusieurs boutiques pour les commerçants.', 'jago-danaya', ['real-jago-boutique.jpg']],
            ['Ann Express Yélimané', 'ann-express-yelimane', 'Gestion transport', 'Site de réservation et gestion du transport (billets, colis, voyages) pour la compagnie Ann Express Yélimané, sur les lignes Bamako – Kayes – Yélimané.', 'sirali', ['real-annexpress.jpg', 'real-annexpress-logo.jpg']],
            ['Wari Niouma', 'wari-niouma', 'Gestion d\'entreprise', 'Outil de gestion pour Wari Niouma Transport.', 'sirali', ['real-wariniouma.jpg', 'real-wariniouma-bus.jpg']],
            ['Diallo et Frères Services', 'diallo-et-freres-services', 'Gestion de l\'or', 'Logiciel ORGEST pour le bureau d\'achat et de vente d\'or Diallo et Frères Services : achats, ventes, stock et trésorerie.', 'orgest', ['real-diallo.jpg']],
            // Projets réalisés par CSB (dossiers Gestion_magasin, Numerise_vignette, DojoManager_laravel, SYNEM du poste de développement).
            ['MagaStock — magasin de l\'Université de Ségou', 'magastock-universite-de-segou', 'Gestion de stock / administration', 'Application web de gestion du magasin de l\'Université de Ségou : entrées avec écarts de livraison, sorties contrôlées, stock en temps réel, bons officiels en PDF et rapports.', null, ['real-magastock.jpg', 'real-magastock-stock.jpg'], [
                'client_name' => 'Université de Ségou', 'year' => 2026,
                'context' => 'Le magasinier de l\'Université enregistrait les entrées et les sorties de matériel sur des bons, bordereaux et fiches papier.',
                'need' => 'Connaître le stock à tout moment, retrouver l\'historique, documenter les écarts de livraison et produire les bons et états sans changer la logique des documents papier.',
                'solution' => 'Entrées et sorties en deux temps (brouillon puis validation), contrôle du stock sous verrou, bon de sortie au modèle officiel du Rectorat, bon d\'entrée, fiche de mouvement, rapports PDF et Excel, import Excel des articles, sauvegarde quotidienne et journal d\'audit.',
                'technologies' => ['Laravel', 'MariaDB', 'Bootstrap 5', 'PDF (DomPDF)', 'Excel'],
            ]],
            ['VigiMoto — vignettes de motos', 'vigimoto-vignettes-motos', 'Sécurité / administration publique', 'Plateforme de numérisation des vignettes de motos et de contrôle des motos volées : Web pour les commissariats et les mairies, application Android pour la police de patrouille et la population.', null, ['real-vigimoto.jpg', 'real-vigimoto-commissariat.jpg'], [
                'year' => 2026,
                'context' => 'L\'obtention de la vignette prenait du temps et les déclarations de vol n\'étaient pas partagées entre les agents.',
                'need' => 'Centraliser les données des motos et de leurs propriétaires, consultables 24 h sur 24, et réduire les délais de délivrance de la vignette.',
                'solution' => 'Enregistrement au commissariat, validation et remise en mairie, déclaration de vol consultable par tous les agents, contrôle rapide sur mobile, SMS au propriétaire quand la moto est retrouvée. Réalisé en équipe.',
                'technologies' => ['Laravel', 'API REST', 'React Native', 'Android', 'MariaDB'],
            ]],
            ['DojoManager — fédération d\'arts martiaux', 'dojomanager-federation-arts-martiaux', 'Sport / fédération', 'Gestion administrative, pédagogique et financière d\'une fédération d\'arts martiaux : disciples, grades, licences, cotisations et ceintures noires.', null, ['real-dojomanager.jpg', 'real-dojomanager-salle.jpg'], [
                'year' => 2026,
                'context' => 'Une fédération de taekwondo organisée en ligues et en salles, avec une première version de l\'application en Spring Boot et React.',
                'need' => 'Suivre les disciples, les passages de grades, les licences et les cotisations à chaque niveau (fédération, ligue, salle).',
                'solution' => 'Réécriture complète en Laravel : cloisonnement fédération → ligue → salle, cartes de licence avec QR code, reçus, attestations et listes de candidats en PDF, application installable (PWA).',
                'technologies' => ['Laravel', 'MySQL', 'Bootstrap 5', 'PDF', 'QR code', 'PWA'],
            ]],
            ['SYNEM — Syndicat National des Enseignants du Mali', 'synem-syndicat-enseignants-mali', 'Syndicat / association', 'Site et plateforme des militants du Syndicat National des Enseignants du Mali : adhésions en ligne, cartes de membre, documents réservés, publications et administration du contenu.', null, ['real-synem.jpg', 'real-synem-militants.jpg', 'real-synem-classe.jpg'], [
                'client_name' => 'SYNEM', 'year' => 2026,
                'context' => 'Le syndicat avait besoin d\'une présence en ligne et d\'un moyen simple de gérer ses militants partout au Mali.',
                'need' => 'Recevoir les adhésions, valider les militants, leur donner accès aux documents et publier les actualités du syndicat.',
                'solution' => 'Site public administrable (accueil, mission, historique, documents, contact), soumission des militants avec validation et réponse par e-mail, campagnes de cartes de membre avec vérification, espace documents des militants, export des militants.',
                'technologies' => ['Laravel', 'MySQL', 'Tailwind CSS', 'PWA'],
            ]],
        ];

        foreach ($items as $order => $item) {
            [$title, $slug, $sector, $summary, $logicielSlug, $images] = $item;
            $extra = $item[6] ?? [];
            if (Realisation::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }

            $ids = array_map(fn ($file) => $this->media($medias, $file, $title, 'Réalisations'), $images);
            // Les détails fournis ($extra) priment sur les valeurs par défaut (client absent = nom du projet).
            $realisation = Realisation::create($extra + [
                'title' => $title, 'slug' => $slug, 'client_name' => $logicielSlug === null || in_array($title, ['KalanNet', 'JÀGO DÁNAYA'], true) ? null : $title, 'sector' => $sector,
                'summary' => $summary, 'logiciel_id' => $logiciel($logicielSlug), 'cover_media_id' => array_shift($ids), 'is_published' => true, 'sort_order' => $order,
            ]);
            $medias->syncGallery($realisation, $ids);
        }
    }

    /** Importe une image du dossier assets/csb dans la bibliothèque (une seule fois par fichier). */
    private function media(MediaService $medias, string $file, string $alt, string $folder): int
    {
        if (isset($this->imported[$file])) {
            return $this->imported[$file];
        }

        $existing = Media::where('original_name', $file)->value('id');
        if ($existing) {
            return $this->imported[$file] = $existing;
        }

        $path = self::ASSETS.$file;
        $mime = mime_content_type($path) ?: 'image/jpeg';
        $media = $medias->store(new UploadedFile($path, $file, $mime, null, true), null, $alt, $folder);

        return $this->imported[$file] = $media->id;
    }
}
