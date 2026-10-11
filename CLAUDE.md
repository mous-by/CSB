# Site CSB — guide du projet (dépôt `CSB`)

Ce fichier est la **seule documentation** du dépôt. Il se lit en entier avant de commencer, par le développeur comme par son assistant IA (Claude Code le charge automatiquement). Langue de travail : français. Source officielle des exigences : `docs/cahier_des_charges.pdf` (cahier des charges fonctionnel et technique V1.0 du 11/10/2026, Computer Service Barry).

## 0. Règles pour l'assistant IA

1. Le cahier des charges est la source principale. Ce qui n'y est pas est « À VALIDER AVEC CSB » ; toute idée non prévue est « PROPOSITION TECHNIQUE — À VALIDER » et n'est jamais une exigence.
2. Ne crée ni table ni fonctionnalité « parce qu'elle semble logique » : vérifie d'abord le cahier, les décisions (§5) et le modèle de données (§4.2).
3. **L'administration utilise exclusivement le gabarit de `Gestion_magasin`** (lui-même issu de VigiMoto) : socle (authentification, rôles et permissions, audit, sauvegardes), thème « Semi Bleu », sidebar, modales, DataTables. Le socle ne se modifie qu'après revue. Un module s'ajoute **sans** modifier les fichiers du socle (§6).
4. Le **site public** a son propre gabarit (`layouts/site.blade.php`, `public/assets/site/css/site.css`) aux couleurs de CSB (cahier §8). Il ne charge jamais le thème de l'administration, et inversement.
5. Aucun mot de passe, secret, ni numéro personnel dans Git. Les coordonnées publiques de l'entreprise (cahier, Annexe A) sont du contenu destiné au site : elles vivent dans `config/site.php` et se modifient dans l'administration. Aucune ressource externe (CDN, police Google, carte intégrée) : assets locaux (D15), y compris sur le site public.
6. Toute action sensible est journalisée dans `audit_logs`. Tout modèle métier utilise `LogsActivity`. Les permissions ne se créent pas « à la main » dans la base (§4.4).
7. Tout contenu saisi dans l'administration est affiché **échappé** ; le texte riche passe par `App\Support\Markdown` (HTML brut supprimé, liens dangereux refusés). Jamais `{!! !!}` sur une saisie non filtrée.
8. Avant de conclure une tâche : `composer test` vert, `vendor/bin/pint` propre, page vérifiée dans le navigateur (Chrome headless, mobile 390 px et ordinateur 1366 px).
9. Un fichier `.md` de plus n'est pas souhaité : mets à jour **ce fichier**.
10. L'assistant ne commite ni ne pousse que si son développeur le demande.

### 0.1 Début de session

1. Lis ce fichier en entier, puis la section du cahier du module à construire.
2. Vérifie l'environnement (§3) : `composer install`, `.env`, les deux bases, `php artisan migrate --seed`, puis `composer test` **vert avant ta première modification**.
3. **Imite l'existant, ne réinvente pas.** Fichiers de référence :

| Besoin | Fichier de référence |
|---|---|
| Manifeste de module (permissions, défauts par rôle, menu) | `config/modules/users.php`, `config/modules/aide.php` |
| Écran d'administration complet (DataTables, pastilles, fiche et formulaire en modale) | gabarit : `Gestion_magasin/resources/views/articles/*`, `…/Referentiels/ArticleController.php` |
| Écran de référentiel simple | gabarit : `Gestion_magasin/resources/views/referentiels/_simple.blade.php` |
| Paramètres modifiables dans l'interface, audités | `app/Support/SiteSettings.php`, `app/Models/AppSetting.php` |
| Page publique (SEO, fil d'Ariane, appel à l'action) | `app/Http/Controllers/Site/PageController.php`, `resources/views/site/pages/about.blade.php`, `resources/views/site/partials/*` |
| Métadonnées d'une page publique | `app/Support/Seo.php` |
| Catalogue public filtré en AJAX, fiche avec galerie | `app/Http/Controllers/Site/LogicielController.php`, `resources/views/site/logiciels/*`, `public/assets/site/js/site.js` |
| Écran d'administration simple (liste + modales) | `resources/views/admin/_crud.blade.php` (ex. `prestations/index.blade.php`) |
| Fiche longue sur page dédiée, images, SEO, publication | `LogicielController` (admin), `LogicielRequest`, `app/Services/Contenus/LogicielService.php`, `resources/views/logiciels/form.blade.php`, `admin/_media-picker`, `admin/_seo-fields` |
| Images (validation, redimensionnement, galeries) | `app/Services/Medias/MediaService.php` |
| Formulaire public envoyé en AJAX, anti-spam, notification | `app/Http/Controllers/Site/DemandeController.php`, `app/Http/Requests/Site/DemandeRequest.php`, `app/Services/Demandes/DemandeService.php`, `resources/views/site/demandes/*` |
| Effets visuels du site (héros, cartes 3D, boutons) | `public/assets/site/css/site-fx.css`, `public/assets/site/js/site.js` (attributs `data-tilt`, `data-magnetic`, `data-rotator`, `data-spotlight`, `data-timeline`) |
| Logique métier dans un service | `app/Services/Users/UserProvisioningService.php` |
| Modèle audité / Policy | `app/Models/User.php`, `app/Models/Concerns/LogsActivity.php`, `app/Policies/UserPolicy.php` |
| Tests | `tests/Concerns/BuildsFoundation.php`, `tests/Feature/Users/UserScreenTest.php`, `tests/Architecture/FoundationRulesTest.php` |

4. **Pièges connus** (hérités du gabarit) :
   - `composer test` tourne sur `db_csb_test`, jamais sur la base de développement.
   - MariaDB 10.4 (XAMPP) : pas de requête JSON (une colonne JSON se stocke en texte et se lit par cast `array`), pas d'`enum` SQL (chaîne + enum PHP).
   - `User::roleName()` et non `role()` ; un seul rôle par utilisateur. Les numéros de connexion passent par `App\Support\PhoneNumber::normalize()` (E.164).
   - Pas d'`update()` de masse sans audit (test d'architecture). Pas de permission créée à la main (manifeste + `php artisan permissions:sync`).
   - Blade : ne jamais écrire `@php(...)` en ligne avant un bloc `@php … @endphp` dans la même vue (test d'architecture).
   - Aucune URL absolue dans les vues (test d'architecture) : les URL externes (schema.org, wa.me) se construisent en PHP.
   - Les clés de `SiteSettings` contiennent des points (`contact.email`) : ne jamais les lire par `config('site.defaults.contact.email')`.
   - Un serveur déjà lancé sur le port 8000 est peut-être celui d'un autre projet : `php artisan serve --port=8001`.
   - Un `@foreach (… as [$title, …])` dans une vue écrase les variables des partiels inclus ensuite (`site.partials.cta` lit `$title`) : nommer les variables de boucle autrement.
   - Les anciennes adresses de contenus sont redirigées par le gestionnaire d'erreurs 404 (`bootstrap/app.php`, `Redirection::follow`), pas par une route.
   - Blade : une directive collée à un mot n'est pas compilée (`commande@if (…)` reste du texte et casse la vue) : laisser une espace ou utiliser une expression `{{ … ? … : '' }}`.
   - **Cache : jamais d'objet** (modèle, collection) dans `Cache::remember` : Laravel 13 refuse de les désérialiser (`cache.serializable_classes` = false) et la page plante à la deuxième visite. Mettre en cache des tableaux. Les tests tournent en cache `array` (sans sérialisation) : `PublicPagesTest` force un cache fichier pour s'en protéger.
   - Bootstrap local = **5.0.0** : un déclencheur `data-bs-toggle` doit être un `<button>` (un `<a href="https://…">` est lu comme sélecteur CSS → erreur JS, menu bloqué).
   - Les e-mails et l'audit de l'administration prennent l'utilisateur par `Auth::guard('web')` : un client connecté (garde `client`) n'est jamais l'auteur d'une action de l'équipe.
   - Vérifier une page JavaScript : `node --check` sur les scripts rendus et Chrome headless (`puppeteer-core` installé dans un dossier temporaire, jamais dans le projet).

## 1. Le projet

Site web professionnel de **Computer Service Barry (CSB)** — « Qualité • Fiabilité • Performance » : vitrine officielle, catalogue de logiciels et de matériel informatique, réalisations, actualités et **outil de génération de prospects** (demandes de devis, de démonstration, de disponibilité, contact). Pas de boutique en V1 : aucun panier ni paiement (cahier §1.1).

| Acteur (cahier §7.1) | Rôle technique | Périmètre V1 |
|---|---|---|
| Administrateur | `administrateur` | Gestion complète : paramètres, utilisateurs, contenus et demandes |
| Gestionnaire de contenu | `gestionnaire` | Pages, articles, logiciels, matériel, réalisations, médias ; pas les paramètres sensibles sauf autorisation |
| Commercial | `commercial` | Consultation et traitement des demandes (statuts, notes) ; pas de gestion des comptes |
| Développeurs | `superadmin` | Maintenance de la plateforme, rôles, permissions, audit, système |
| Visiteurs | — | Site public, sans compte (espace client hors V1) |

**Hors périmètre V1** (cahier §3.2) : espace client, panier et paiement, stock synchronisé, application mobile, chatbot, notifications WhatsApp automatiques, version anglaise, comptabilité. **CSB a demandé le 2026-10-11 de les construire** : espace client, boutique (paiement confirmé à la main), synchronisation du stock, alertes WhatsApp et tickets sont faits ; la version anglaise est abandonnée ; application mobile, chatbot et comptabilité restent hors périmètre. Listés dans « Modules à venir » (`config/planned_modules.php`, cahier §15.3).

**Objectifs mesurables** (cahier §2) : visites, clics WhatsApp, formulaires reçus, demandes qualifiées, pages les plus consultées, conversion.

## 2. Où en est-on

**Fait — phase 0, socle** (2026-10-11) :
- Reprise du socle de `Gestion_magasin` sans aucun module du magasin : Laravel 13, PHP ≥ 8.3, MariaDB, Blade + Bootstrap 5 + thème « Semi Bleu » local, Spatie Permission. Bibliothèques PDF et Excel retirées.
- Authentification par **numéro de téléphone** + mot de passe (gabarit, À VALIDER), mot de passe temporaire à changer, 5 tentatives, comptes désactivables, superadmins créés depuis `.env`.
- Rôles du cahier §7.1 (`administrateur`, `gestionnaire`, `commercial`) + `superadmin` ; écrans Utilisateurs, Rôles, Permissions, Attribution, Audit, Système (menu **Paramètres**), Aide, sauvegarde quotidienne (`backup:run`, `backup:verify`).
- **Administration sous `/admin`** (cahier §7 « URL dédiée », `ADMIN_PATH`), **site public à la racine** (routes `site.*`).
- Site public : gabarit propre (bleu `#103B8E`, orange `#F58220`, Inter locale, mobile d'abord, lien d'évitement, focus visible), barre de contact, menu, pied de page, bouton WhatsApp flottant avec message prérempli (nom de la page), bouton d'appel sur mobile, Open Graph, canonique, JSON-LD `Organization`. Pages : accueil, à propos, mentions légales, confidentialité, pages d'erreur publiques (404 avec retour à l'accueil et accès à la navigation).
- `App\Support\SiteSettings` : paramètres du site modifiables et audités (entreprise, coordonnées, réseaux, accueil, à propos, pages légales, SEO), défauts dans `config/site.php`.
- 225 tests verts, Pint propre.

**Fait — phases 1 à 3** (2026-10-11) :
- **Pages et paramètres** (`/admin/pages`, onglets) : entreprise, coordonnées, réseaux, accueil, à propos, pages légales (Markdown), référencement ; `pages.update` (gestionnaire) / `pages.settings` (administrateur, gestionnaire en exception). **Services** (prestations) et **témoignages** (masqués par défaut) ; page publique Services, sections de l'accueil.
- **Médias** : bibliothèque (type réel vérifié, 5 Mo, nom généré, redimensionnement 1600 px, vignette 480 px, EXIF retiré, orientation corrigée), sélecteur d'images Select2 avec vignettes et envoi direct dans les formulaires, image utilisée non supprimable.
- **Catégories** (logiciel, matériel, actualités) et **logiciels** : fiche du cahier §5.2 sur page dédiée, statuts, plateformes, catégories secondaires, logo, captures, vidéo en lien, SEO, publication / dépublication / archivage (`logiciels.publish`, audités), logiciels phares sur l'accueil ; catalogue public filtrable en **AJAX** (secteur, plateforme, recherche), fiche avec galerie plein écran, JSON-LD `SoftwareApplication`.
- Contenu initial (`ContenuInitialSeeder`, idempotent, inclus dans `db:seed`) : 6 services et les catégories du cahier.
- 255 tests verts.

**Fait — phases 4 à 7** (2026-10-11) :
- **Matériel** : fiche du cahier §5.4 (caractéristiques « Libellé : valeur », état, prix facultatif en FCFA, conditions, disponibilité déclarée, photos, SEO), catalogue public filtrable en AJAX (catégorie, état, disponibilité, recherche), JSON-LD `Product` (offre seulement si un prix est renseigné).
- **Réalisations** (client facultatif, contexte, besoin, solution, technologies, visuels, lien vers un logiciel) et **actualités** (Markdown avec onglet « Aperçu » en AJAX, compteur de mots, date de publication programmable, aperçu sur le site avant publication, JSON-LD `Article`, partage WhatsApp, copie du lien).
- **Demandes** (cahier §6) : formulaires contact, devis (pièce jointe privée : PDF, Word, Excel, OpenDocument, JPEG, PNG, 5 Mo), démonstration (fiche logiciel préremplie), prix / disponibilité (fiche matériel préremplie) ; envoi AJAX, erreurs sous les champs, données conservées en cas d'échec ; anti-spam sans service externe (`SpamGuard` : champ piège + délai minimal de 3 s, robot = fausse réussite), `throttle:demandes` (5/min, 30/jour par IP) ; notification e-mail après enregistrement (`NouvelleDemande`, « Répondre » écrit au visiteur), échec visible et « Réessayer ». Administration : liste filtrable, fiche de traitement (statuts du §6.5 audités, prise en charge = attribution, motif de clôture, notes internes ajoutées en AJAX, historique), export CSV pour Excel audité. Tableau de bord du commercial (tâches, volumes par statut, graphique des 14 derniers jours, taux de conversion du mois), pastille du menu, cloche.
- **Référencement et recherche** (cahier §11.1) : `sitemap.xml` (contenus publiés), `robots.txt` (hors production : rien n'est indexé ; en production : tout sauf l'administration), redirections 301 automatiques quand l'adresse d'un contenu change (table `redirections`, chaînes raccourcies), recherche globale (`/recherche`) et **suggestions en direct** dans l'en-tête (touche « / »).
- **Rendu « vitrine »** (demande de CSB) : héros animé (réseau de particules sur canvas, formes lumineuses, halo qui suit la souris, maquette 3D en verre dépoli qui suit la souris, puces flottantes, mots qui défilent — réglables dans Pages et paramètres → Accueil), cartes 3D avec reflet (`data-tilt`), boutons magnétiques et onde au clic, bandeau défilant, bande « Pourquoi CSB » en verre, frise de la méthode qui se dessine, transitions entre pages. `public/assets/site/css/site-fx.css` + `site.js`.
- **Guide d'utilisation** (menu Aide) : un chapitre par tâche (demandes, pages, images, logiciels, matériel, réalisations, actualités, référencement, comptes).
- 282 tests verts, Pint propre ; parcours vérifiés dans Chrome headless (catalogue AJAX sans rechargement, formulaire AJAX, galerie plein écran, ordinateur 1366 px et mobile 390 px).

**Fait — maquette de l'accueil, saisie en masse, évolutions du cahier §15.3** (2026-10-11, à la demande de CSB qui a demandé de « terminer le tout y compris les modules à venir ») :
- **Accueil refait d'après la maquette validée** : barre de contact, logo, méga-menu « Nos solutions », héros (titre, points, visuel), valeurs, solutions phares, catégories de matériel, **carrousel du matériel** (promotions d'abord), **vitrine des réalisations qui défilent** (grande diapositive, vignettes, barre de progression, glissement au doigt, pause au survol), « Pourquoi CSB », chiffres ; textes, points et images réglables (Pages et paramètres → Accueil). Contenu réel (`ContenuCsbSeeder`, hors `db:seed`).
- **Promotions du matériel** : `promo_price` + `promo_ends_on` facultative ; pendant la promotion, prix normal **barré**, prix promo en rouge, ruban « Promo -X % » sur la carte, offre JSON-LD au prix promo ; pastille « En promotion » dans l'administration ; promo terminée = prix normal seul.
- **Saisie en masse** (« ajouter des lignes » / « ajouter un fichier ») : `BulkDefinition` + `BulkService` (grille, collage depuis Excel, modèle .xlsx, import tout-ou-rien) pour services, témoignages, catégories, matériel, logiciels, réalisations, abonnés, clients ; éditeur de lignes dans les formulaires (fonctionnalités, caractéristiques, technologies).
- **Newsletter** (abonnés, désinscription en un clic, envoi d'une actualité en file d'attente).
- **Espace client** (`/espace-client`, garde `client`) : inscription, confirmation de l'adresse (rattache les demandes antérieures), connexion par e-mail ou téléphone, mot de passe oublié, demandes, documents privés déposés par l'équipe, profil.
- **Tickets d'assistance** : le client ouvre un ticket (`TCK-AAAA-NNNN`, `SequenceService`), pièce jointe privée, fil d'échanges ; l'équipe répond (e-mail au client) ou ajoute une **note interne** invisible du client, change statut / priorité / attribution ; une réponse du client rouvre un ticket résolu ; pastille du menu et cloche ; manifeste `tickets` (administrateur, commercial).
- 305 tests verts, Pint propre ; carrousels, méga-menu et navigation vérifiés dans Chrome headless.
- **Accueil** : « Nos solutions logicielles » et « Matériel informatique » en **bandes qui défilent en boucle continue** (`[data-loop]` dans `site.js` : cartes dupliquées, sans saut, pause au survol), cartes compactes.
- **Réalisations réelles** (`ContenuCsbSeeder`) : KalanNet, JÀGO DÁNAYA, Ann Express Yélimané, Wari Niouma, Diallo et Frères Services, **MagaStock** (Université de Ségou), **VigiMoto**, **DojoManager**, **SYNEM**, avec leurs images (`database/seeders/assets/csb/real-*.jpg`). `ContenuCsbSeeder` est appelé par `DatabaseSeeder` : le contenu réel fait partie du site hébergé.
- **Données de test** : `php artisan db:seed --class=DonneesTestSeeder` (jamais en production, refus) : prix et promotions du matériel, tarifs des logiciels, 3 actualités, 3 témoignages, 3 clients `*@test.csb.ml` (mot de passe généré, affiché à la fin), 5 demandes, 2 tickets, 3 abonnés. Prix, témoignages, clients et demandes sont **fictifs** : à supprimer avant la mise en ligne.
- **Connexion de l'administration** : logo de CSB seul sur fond bleu, sans diaporama (demande de CSB).
- **Boutique en ligne** (manifeste `boutique`, routes `site.panier*`, `site.commande*`, `commandes.*`) : bouton « Ajouter au panier » sur les cartes et la fiche (seul un matériel publié, avec un prix, pas « indisponible »), panier en session mis à jour en AJAX (compteur de l'en-tête, quantités, retrait), commande sans compte ou préremplie pour un client connecté (retrait ou livraison, espèces / Orange Money / Moov Money / Wave), numéro `CMD-AAAA-NNNN`, **prix figés** dans `commande_lignes` (promotion comprise), page de suivi par lien secret (`/commande/{numéro}/{jeton}`), e-mails à l'équipe et au client à chaque statut. Pas de paiement en ligne : l'équipe confirme par téléphone puis enregistre le paiement et sa référence (prestataire en ligne : À VALIDER). Numéros marchands et textes de retrait / livraison : Pages et paramètres → Boutique. Administration : liste (chiffre du mois, filtres), fiche de traitement (statut, référence, motif d'annulation obligatoire), export CSV, pastille et cloche ; « Mes commandes » dans l'espace client.
- **Synchronisation du stock** (manifeste `synchro`, écran Contenus du site → Synchronisation du stock) : chaque matériel a une **référence** (code de l'article dans le logiciel de stock, unique, en majuscules) et un **stock synchronisé** (`stock_quantity`, `stock_synced_at`). Mise à jour par **API** (`POST /api/v1/stock`, jeton `Authorization: Bearer csb_…` créé et révoqué dans l'écran, seule son empreinte SHA-256 est en base ; `GET /api/v1/stock` liste les références ; 60 appels / min) ou par **fichier** Excel / CSV (colonnes Référence, Quantité, Prix facultatif ; modèle .xlsx). Tout ou rien ; quantité > 0 → Disponible, 0 → Indisponible ; un prix reçu remplace le prix (promotion devenue trop haute retirée) ; références inconnues signalées, jamais créées ; journal `stock_syncs` + audit `synchro.applied` (canal `api`). Le panier est limité au stock connu ; la fiche affiche « En stock : N ».
- **Alertes WhatsApp** (manifeste `whatsapp`, Paramètres → Alertes WhatsApp) : à chaque nouvelle demande, commande ou ticket (événements au choix), un message **modèle approuvé** par Meta (WhatsApp Business Cloud API, un paramètre de texte) part vers chaque numéro de l'équipe, **en file d'attente** (`SendWhatsAppAlert`, 3 essais) : le visiteur n'attend jamais et un échec est journalisé (`whatsapp.failed`, listé sur l'écran) sans rien bloquer. Message de test immédiat. Configuration dans `.env` (`WHATSAPP_*`, `config/services.php`) ; compte Meta et modèle de message : à fournir par CSB.
- **Comptes clients** : mot de passe simple accepté (4 caractères au moins, « 1234 ») à la demande de CSB (`Client::passwordRules()`) ; l'administration garde sa règle forte. Bouton « œil » sur tous les champs de mot de passe du site (`partials.password-toggle` inclus dans `layouts.site`). Numéros Orange Money / Moov Money / Wave : plusieurs numéros séparés par « / » (type de réglage `tels`).
- **Modules à venir** : liste vide (`config/planned_modules.php`), toutes les évolutions retenues sont construites. Guide d'aide complété (saisie en lot, boutique, stock, espace client et tickets, WhatsApp).
- 313 tests verts, Pint propre ; 29 pages de l'administration et du site parcourues dans Chrome headless sans erreur. **Version anglaise abandonnée** (demande de CSB, 2026-10-11).

**Reste** (dépend de CSB) : contenus réels (logo, textes officiels, logiciels avec captures, matériel avec prix, réalisations autorisées, témoignages), mentions légales, SMTP et destinataire des notifications, audit de l'hébergement et mise en ligne HTTPS (phases 8 et 9). 

## 3. Démarrer

Prérequis : PHP ≥ 8.3 (extensions `gd`, `mbstring`, `fileinfo`), Composer 2, MariaDB 10.4+ ou MySQL 8. Aucun Node (D19).

```sh
composer install
cp .env.example .env
php artisan key:generate
# créer deux bases vides (utf8mb4, utf8mb4_unicode_ci) : db_csb et db_csb_test
php artisan migrate --seed      # rôles, permissions, superadmins déclarés dans .env
php artisan storage:link        # images du site (disque public)
php artisan serve --port=8001   # site : http://localhost:8001 — administration : http://localhost:8001/admin
composer test                   # base db_csb_test, jamais la base de développement
```

Dans `.env` (jamais dans Git) : `SUPERADMIN_PASSWORD`, `SUPERADMIN_1_NAME` / `_PHONE` ; `ADMIN_PATH` ; `SITE_NOTIFICATION_EMAIL` et la configuration SMTP (`MAIL_*`) pour les notifications des demandes ; `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_TEMPLATE` pour les alertes WhatsApp (vides = désactivées).

## 4. Architecture

```
Visiteur → routes/site.php (site.*) → contrôleurs App\Http\Controllers\Site → vues site/* (layouts.site)
Équipe   → /admin + routes/web/*.php → session → Middleware (auth, active, password.changed, permission:…)
          → contrôleurs minces → Services (transactions) → Modèles → MariaDB
                                     ↑ ActivityLogger → audit_logs (même transaction)
```

1. La logique vit dans des **Services** (enregistrement d'une demande, changement de statut, publication, téléversement d'image), jamais dans les contrôleurs.
2. Autorisation en trois couches : permission (`module.action`) → Policy → règles du service. Le superadmin contourne l'autorisation (`Gate::before`), pas la logique métier ni l'audit.
3. Le site public ne lit que les contenus **publiés** (scope `published()`), jamais un brouillon ni un contenu supprimé.
4. Les formulaires publics sont protégés : CSRF, validation serveur, limitation de débit (`throttle`), champ piège anti-robot et délai minimal de saisie (cahier §10).

### 4.1 Tables du socle (13)

`users` (connexion par téléphone, sans e-mail), `roles`, `permissions` (`source` = `manifest` / `custom`), `model_has_roles` (un seul rôle), `model_has_permissions`, `role_has_permissions`, `audit_logs` (non modifiable), `app_settings` (clé / valeur des paramètres du site), `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### 4.2 Modèle de données métier (✔ = créée)

Cahier §9.1. Colonnes proposées (PROPOSITION TECHNIQUE — À VALIDER). Noms de tables en français, colonnes en anglais. Tout en `softDeletes` ; clés étrangères `restrictOnDelete` ou `nullOnDelete`, **jamais de cascade vers les demandes** (cahier §9.1).

| Table | Colonnes principales | Règles |
|---|---|---|
| `categories` ✔ | `type` (`logiciel`, `materiel`, `article`), `name`, `slug`, `description`, `sort_order`, `is_active` | slug unique par type ; utilisée = se désactive au lieu de se supprimer |
| `medias` ✔ | `path`, `thumb_path`, `original_name`, `mime`, `size`, `width`, `height`, `alt`, `folder`, `uploaded_by` | Images seulement (JPEG, PNG, WebP), type réel vérifié, ≤ 5 Mo, nom généré, redimensionnées (cahier §9, §10) |
| `mediables` ✔ | `media_id`, `mediable_type`, `mediable_id`, `position` | Galeries (captures, photos) |
| `prestations` ✔ | `title`, `slug`, `icon`, `summary`, `description`, `sort_order`, `is_published` | Page Services (cahier §4) |
| `temoignages` ✔ | `author_name`, `author_title`, `organization`, `content`, `sort_order`, `is_published` | « Témoignages autorisés » de l'accueil |
| `logiciels` ✔ | `name`, `slug`, `summary`, `problem`, `description`, `category_id`, `features` (JSON), `platforms` (JSON), `status`, `price_text`, `video_url`, `logo_media_id`, `is_published`, `published_at`, `is_featured`, `sort_order`, `seo_title`, `seo_description`, `seo_media_id` | Cahier §5.1, §5.2 ; statut `disponible`, `sur_demande`, `en_developpement`, `archive` |
| `categorie_logiciel` ✔ | `category_id`, `logiciel_id` | Catégories secondaires |
| `materiels` ✔ | `designation`, `slug`, `category_id`, `brand`, `model`, `description`, `specs` (JSON), `condition`, `price`, `promo_price`, `promo_ends_on`, `price_conditions`, `availability`, `main_media_id`, `is_published`, `published_at`, `is_featured`, `sort_order`, SEO | Cahier §5.3, §5.4 ; état `neuf`, `occasion`, `reconditionne` ; disponibilité `disponible`, `sur_demande`, `indisponible` |
| `realisations` ✔ | `title`, `slug`, `client_name`, `sector`, `year`, `context`, `need`, `solution`, `technologies` (JSON), `cover_media_id`, `logiciel_id`, `is_published`, `published_at`, `sort_order`, SEO | Projets autorisés à la publication |
| `articles` ✔ | `title`, `slug`, `excerpt`, `body`, `category_id`, `cover_media_id`, `author_id`, `status` (`brouillon`, `publie`), `published_at`, SEO | Actualités et conseils |
| `demandes` ✔ | `type` (`contact`, `devis`, `demonstration`, `disponibilite`), `status`, `name`, `organization`, `email`, `phone`, `subject`, `message`, `need_type`, `budget`, `deadline`, `availability`, `subject_type`/`subject_id`/`subject_label`, `source_url`, `attachment_*`, `consent_at`, `ip_address`, `user_agent`, `assigned_to`, `closed_reason`, `status_changed_at`, `notified_at`, `notification_error` | Statuts du cahier §6.5 ; jamais supprimée par cascade |
| `demande_notes` ✔ | `demande_id`, `user_id`, `body` | Notes internes (personnel autorisé) |
| `abonnes` ✔ | `email`, `name`, `token`, `source`, `subscribed_at`, `unsubscribed_at`, `ip_address` | Newsletter (désinscription en un clic) |
| `clients` ✔, `client_documents` ✔, `client_password_reset_tokens` ✔ | compte client (`name`, `organization`, `email`, `phone`, `password`, `is_active`, `email_verified_at`) ; documents privés (disque local) | Espace client (cahier §15.3) |
| `commandes` ✔, `commande_lignes` ✔ | `number`, `client_id`, `status` (`nouvelle`, `confirmee`, `payee`, `livree`, `annulee`), coordonnées, `delivery_method`, `payment_method`, `payment_reference`, `total`, `access_token`, dates ; lignes `designation`, `unit_price`, `normal_price`, `quantity`, `line_total` | Boutique ; prix figés à la commande |
| `api_tokens` ✔, `stock_syncs` ✔ ; `materiels.reference`, `stock_quantity`, `stock_synced_at` | jetons de l'API (empreinte, 4 derniers caractères, dernier appel, révocation) ; journal des synchronisations | Synchronisation du stock |
| `sequences` ✔ | `prefix`, `year`, `last_number` | Numérotation sous verrou (`TCK`, `CMD`) |
| `tickets` ✔, `ticket_messages` ✔ | `number`, `client_id`, `subject`, `category`, `priority`, `status`, `device`, `assigned_to`, `awaiting_team`, `last_activity_at`, `closed_at` ; messages `body`, `is_internal`, `attachment_*` | Tickets d'assistance (cahier §15.3) |
| `redirections` ✔ | `from_path`, `to_path`, `hits`, `last_hit_at` | Anciennes adresses → nouvelles (301), créées automatiquement (cahier §11.1) |

### 4.3 Rôles

| Rôle | Niveau | Gère les comptes |
|---|---|---|
| `superadmin` | 100 | tous |
| `administrateur` | 80 | `gestionnaire`, `commercial` |
| `gestionnaire` | 50 | aucun |
| `commercial` | 50 | aucun |

### 4.4 Permissions (deux voies)

Convention `module.action`. **Voie A** : manifeste `config/modules/<module>.php` + `php artisan permissions:sync` (à chaque déploiement). **Voie B** : le superadmin crée des permissions `custom` depuis Paramètres → Permissions. Toutes les routes d'administration portent `permission:module.action`. Réservées au superadmin : `roles.*`, `permissions.*`, `audit.*`, `system.*` (ouvrir l'audit à l'administrateur : À VALIDER, cahier §7 « Journal »).

### 4.5 Audit

`ActivityLogger::log()` pour les actions métier (`demandes.received`, `demandes.status_changed`, `logiciels.published`…), trait `LogsActivity` pour création / modification / suppression. Les lectures ne sont pas journalisées.

## 5. Décisions

| # | Décision |
|---|---|
| D-G | **Gabarit** : socle, thème, conventions et tests repris de `Gestion_magasin` (lui-même issu de VigiMoto), sans les modules du magasin, les PDF ni l'import Excel. Décisions du socle valables : MariaDB, audit, permissions à deux voies, assets locaux (D15), pas de Vite (D19), connexion par téléphone (D26), superadmins automatiques (D27) |
| D-1 | Administration sous `/admin` (configurable `ADMIN_PATH`), site public à la racine ; noms de routes de l'administration inchangés (`home`, `login`, `users.index`…), routes publiques `site.*` |
| D-2 | Contenus riches en **Markdown** (pas d'éditeur WYSIWYG externe) rendu par `App\Support\Markdown` — PROPOSITION TECHNIQUE |
| D-3 | Images stockées sur le disque `public` sous un nom généré, redimensionnées (1600 px) avec une vignette (480 px) par GD ; pièces jointes des devis sur le disque privé `local`, téléchargeables depuis l'administration seulement |
| D-4 | Notifications e-mail envoyées **après** l'enregistrement de la demande : un échec d'envoi ne perd jamais la demande, il est journalisé et visible dans l'administration (REC-07) |
| D-5 | Le catalogue matériel est informatif : prix et disponibilité déclarés à la main (cahier §5.4) ; un prix ne s'affiche que s'il est renseigné, avec sa devise (FCFA) et ses conditions |
| D-6 | Un logiciel « Archivé » n'apparaît plus dans le catalogue mais sa fiche reste accessible par son adresse avec la mention « Archivé » — À VALIDER |
| D-7 | Aucune carte ni vidéo intégrée (ressources externes) : lien de localisation et lien vers la vidéo, ouverts dans un nouvel onglet |
| D-8 | **Site public très dynamique et soigné** (demande de CSB, 2026-10-11) : catalogues filtrés et recherchés en AJAX sans rechargement (`form[data-ajax-filter]` → `[data-ajax-results]`, adresse et bouton Retour à jour), formulaires envoyés en AJAX avec erreurs sous les champs (`form[data-ajax-form]`, réponse JSON), animations d'apparition, compteurs, galerie plein écran, notifications. Tout dans `public/assets/site/js/site.js` (JavaScript local, sans bibliothèque) ; chaque comportement est une amélioration : sans JavaScript, la page fonctionne (liens et formulaires classiques). Respecte `prefers-reduced-motion` |

### Points ouverts — À VALIDER AVEC CSB (cahier §16)

Logo original et couleurs exactes ; textes officiels (présentation, mission, vision) et adresse publique ; horaires et liens sociaux ; liste des services, logiciels (statuts, captures), matériel (prix, état), projets publiables ; mentions légales ; hébergement, domaine et SMTP ; connexion de l'équipe par téléphone ou par e-mail ; accès de l'administrateur au journal d'audit.

## 6. Comment ajouter un module

1. Manifeste `config/modules/<module>.php` (permissions, défauts par rôle, `navigation`) puis `php artisan permissions:sync`. Le groupe de la sidebar se déclare dans `partials/sidebar.blade.php` (`$groupOf`).
2. Migrations (`softDeletes`, index, clés étrangères sans cascade vers les demandes) ; tables ajoutées à `tests/Feature/Database/SchemaTest.php` et, si essentielles, à `config/backup.php` (`required_tables`).
3. Modèle avec `LogsActivity`, alias morph dans `AppServiceProvider`, scope `published()` pour le site.
4. Policy, FormRequests (messages en français), Service, contrôleur `Web/Admin`, `routes/web/<module>.php` (`auth`, `active`, `password.changed`, `permission:…`).
5. Vues d'administration au gabarit du thème (§7) ; pages publiques dans `resources/views/site/<module>/` avec `Seo::make()`.
6. Tests : permissions, règles, audit, rendu public (contenu non publié invisible).

## 7. Interface

### Administration (gabarit impératif)

Identique au gabarit `Gestion_magasin` : en-têtes de carte `card-header card-header-brand` avec titre blanc en majuscules ; tableaux DataTables (libellés français) ; boutons d'action `btn btn-primary btn-sm` à icône (`bx-show`, `bx-edit`, `bx-trash`) ; pastilles `nv-chips` ; **modales** pour créer / modifier / consulter ; suppression confirmée par SweetAlert2 ; fil d'Ariane puis `<hr />`. Les formulaires longs (logiciel, article) peuvent avoir une page dédiée, comme les entrées / sorties du gabarit.

### Site public

Bleu `#103B8E`, orange `#F58220` (À CONFIRMER selon le logo), blanc, police Inter. Appels à l'action prioritaires : « Demander un devis », « Demander une démonstration », « Contacter CSB » (cahier §8). Une seule balise `h1` par page ; textes alternatifs ; labels de formulaire ; erreurs reliées aux champs (`aria-describedby`) ; contrastes AA (l'orange du texte est `#c8620c`).

## 8. Exigences → où elles vivent

| Exigence (cahier) | Implémentation | Test |
|---|---|---|
| §10 HTTPS, CSRF, XSS, SQL | middleware `web`, échappement Blade, `Markdown`, Eloquent | `FoundationRulesTest` |
| §10 tentatives de connexion | `CredentialChecker` (5 essais) | `LoginTest` |
| §10 anti-spam des formulaires | `throttle:demandes`, `SpamGuard` (champ piège, délai minimal) | `DemandeFormsTest` |
| §10 fichiers | `MediaService` : type réel, taille, extension, dimensions, nom généré, ré-encodage | `MediaLibraryTest` |
| §10 autorisations | `permission:…` sur chaque route d'administration | `FoundationRulesTest` |
| §11.1 SEO | `Seo`, `SeoController` (sitemap, robots), `Redirection`, JSON-LD des fiches | `SeoAndSearchTest`, `PublicPagesTest` |
| §6 demandes, REC-06, REC-07 | `DemandeService` (enregistrement puis notification), `DemandeRequest` | `DemandeFormsTest`, `DemandeTreatmentTest` |
| REC-11 sauvegarde | `backup:run`, `backup:verify` | `BackupAndDemoTest` |

## 9. Exploitation

### Installation sur un serveur (Linux)

1. PHP ≥ 8.3 (`pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `zip`), Composer 2, MariaDB 10.4+ ou MySQL 8, domaine pointé sur `public/` (aucun accès web au reste du code), **HTTPS obligatoire** (cahier §10, §12).
2. `composer install --no-dev --optimize-autoloader`, `cp .env.example .env`, `php artisan key:generate`.
3. `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, base, `SUPERADMIN_*` (mot de passe fort, temporaire hors `local`), `ADMIN_PATH`, SMTP, `SITE_NOTIFICATION_EMAIL`, `WHATSAPP_*`, `BACKUP_MYSQLDUMP`, `BACKUP_COPY_PATH`. Le cron `schedule:run` est obligatoire : il vide aussi la file d'attente (newsletter, alertes WhatsApp).
4. `php artisan migrate --force && php artisan db:seed --force && php artisan storage:link`.
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Cron : `* * * * * cd /chemin/CSB && php artisan schedule:run >> /dev/null 2>&1` (sauvegarde quotidienne à `BACKUP_TIME`, puis vérification).
7. À chaque mise à jour : `git pull`, `composer install --no-dev`, `php artisan migrate --force`, `php artisan permissions:sync`, `php artisan optimize`.

### Sauvegarde et restauration (cahier §10, REC-11)

- `php artisan backup:run` : `mysqldump --single-transaction` compressé dans `storage/app/backups/`, `BACKUP_KEEP` versions, copie séparée dans `BACKUP_COPY_PATH`. `php artisan backup:verify [fichier]` : archive lisible, dump complet, tables essentielles présentes.
- Les **images** (`storage/app/public`) et les pièces jointes (`storage/app/private`) se sauvegardent avec les fichiers du serveur (copie du dossier `storage/app` hors de l'instance).
- Test de restauration (au moins une fois par mois) : créer une base `db_csb_restore_test`, `gunzip -c <fichier> | mysql … db_csb_restore_test`, compter les lignes de `demandes`, supprimer la base.
- Restauration réelle : `php artisan down`, `php artisan backup:run`, recréer la base, `gunzip -c <fichier> | mysql … db_csb`, `php artisan migrate --force`, `php artisan up`.

## 10. Plan de développement

Étapes du cahier §15.1. Chaque tâche se termine par ses tests et une vérification dans le navigateur.

| Phase | Contenu | Statut |
|---|---|---|
| 0 | Socle repris du gabarit, administration sous `/admin`, gabarit du site public, pages institutionnelles | **Fait** |
| 1 | Pages et paramètres (écran des paramètres du site), services (prestations), témoignages, page Services | **Fait** |
| 2 | Médias : bibliothèque d'images, validation, redimensionnement, sélecteur dans les formulaires | **Fait** |
| 3 | Catégories et logiciels : catalogue filtrable, fiches, statuts, publication, archivage | **Fait** |
| 4 | Matériel : catalogue filtrable, fiches, état, prix, disponibilité | **Fait** |
| 5 | Réalisations, actualités et conseils | **Fait** |
| 6 | Demandes : formulaires publics (contact, devis, démonstration, disponibilité), anti-spam, pièces jointes, notifications e-mail, traitement (statuts, notes), tableau de bord commercial | **Fait** |
| 7 | Référencement : sitemap XML, robots.txt, JSON-LD des fiches, redirections ; recherche ; guide d'utilisation complet | **Fait** |
| 8 | Recette (REC-01 à REC-12) avec CSB, contenus réels | Avec CSB |
| 9 | Audit de l'hébergement, mise en ligne HTTPS, e-mail, sauvegardes, transfert | Avec CSB |

### Critères de recette (cahier §13)

| Critère | Où | Critère | Où |
|---|---|---|---|
| REC-01 Navigation publique | ✔ `PublicPagesTest` | REC-07 Notifications | ✔ `DemandeFormsTest` |
| REC-02 Responsive | toutes | REC-08 Administration protégée | socle ✔ |
| REC-03 Catalogue logiciel | phase 3 ✔ (`LogicielTest`) | REC-09 Sécurité des fichiers | ✔ `MediaLibraryTest`, `DemandeFormsTest` |
| REC-04 Catalogue matériel | ✔ `MaterielTest` | REC-10 SEO de base | ✔ `SeoAndSearchTest` |
| REC-05 Recherche et filtres | ✔ `LogicielTest`, `MaterielTest`, `SeoAndSearchTest` | REC-11 Sauvegarde | socle ✔ |
| REC-06 Formulaires | ✔ `DemandeFormsTest` | REC-12 Documentation | ce fichier, Aide |
