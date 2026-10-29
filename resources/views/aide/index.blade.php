@extends('layouts.admin')

@section('title', 'Aide')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Aide</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Guide d'utilisation</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        // Chapitres : une tâche courante de l'administration par chapitre (cahier §15.1 « Transfert »). Chaque étape est
        // une phrase courte. Chaque module ajoute son chapitre.
        $chapters = [
            ['bx bx-log-in', 'Se connecter et se déplacer', [
                'L\'administration s\'ouvre à l\'adresse du site suivie de /'.config('site.admin_path').'. Connectez-vous avec votre numéro de téléphone et votre mot de passe. À la première connexion, choisissez un nouveau mot de passe (8 caractères au moins, lettres et chiffres).',
                'Le menu de gauche ne montre que les écrans auxquels votre rôle donne accès. Une pastille orange signale ce qui attend votre action.',
                'Le tableau de bord résume la situation ; le bouton « Voir le site » ouvre le site public dans un nouvel onglet.',
                'Votre profil (en haut à droite) permet de changer votre nom, votre numéro et votre mot de passe.',
            ]],
            ['bx bx-envelope', 'Traiter les demandes', [
                'Chaque formulaire du site (contact, devis, démonstration, prix / disponibilité) crée une demande « Nouvelle » et envoie un e-mail à l\'équipe. Une pastille dans le menu et la cloche signalent les nouvelles demandes.',
                'Demandes → bouton « œil » : la fiche montre le demandeur, son message, le contenu concerné et la pièce jointe éventuelle (à télécharger).',
                'Faites évoluer le statut : En cours (prise en charge, la demande vous est attribuée), Devis envoyé, Gagnée, ou Perdue / clôturée avec un motif.',
                'Les notes internes (échanges, relances) ne sont visibles que par l\'équipe. « Répondre par e-mail » et « Appeler » ouvrent directement votre messagerie ou votre téléphone.',
                'Si l\'e-mail de notification n\'a pas pu partir, la fiche l\'indique avec un bouton « Réessayer » : la demande, elle, n\'est jamais perdue.',
                '« Exporter (Excel) » télécharge les demandes filtrées (type, statut, période) dans un fichier qui s\'ouvre dans Excel.',
            ]],
            ['bx bx-file', 'Modifier les pages et les coordonnées', [
                'Pages et paramètres → onglets Accueil, À propos, Pages légales : textes du site. Les textes longs utilisent le Markdown : ## Titre, **gras**, - liste.',
                'Les onglets Entreprise, Coordonnées, Réseaux sociaux et Référencement sont réservés à l\'administrateur : téléphones, WhatsApp, e-mail, adresse, horaires, liens sociaux.',
                'Un champ vidé disparaît du site (par exemple un second téléphone). Chaque modification est enregistrée dans le journal.',
            ]],
            ['bx bx-images', 'Ajouter des images', [
                'Médias → « Téléverser » : images JPEG, PNG ou WebP de 5 Mo au plus. Elles sont redimensionnées automatiquement et leurs données de localisation retirées.',
                'Renseignez le texte alternatif (description de l\'image) : il sert aux personnes malvoyantes et au référencement. La pastille « Sans texte alternatif » les retrouve.',
                'Dans les fiches (logiciel, matériel, réalisation, article), choisissez une image de la bibliothèque ou envoyez-en une nouvelle directement. Une image utilisée ne peut pas être supprimée.',
            ]],
            ['bx bx-code-block', 'Publier un logiciel', [
                'Logiciels → « Ajouter » : nom, résumé, catégorie (secteur), statut, plateformes, fonctionnalités (une par ligne), captures et logo.',
                'N\'annoncez que les fonctionnalités et plateformes réellement disponibles ; le statut (Disponible, Sur demande, En développement) doit correspondre à l\'état réel.',
                'Cochez « Publié » pour l\'afficher sur le site, « Logiciel phare » pour le mettre en avant sur l\'accueil. « Archivé » le retire du catalogue tout en gardant sa page.',
                'Le bouton vert « Publier » de la liste met en ligne en un clic (au téléphone, il est aussi sous le titre) ; l\'œil barré dépublie. Même chose pour le matériel, les réalisations et les actualités. « Catégories » gère les secteurs du catalogue.',
            ]],
            ['bx bx-laptop', 'Gérer le matériel', [
                'Matériel → « Ajouter » : désignation, catégorie, marque et modèle, caractéristiques (« Libellé : valeur », une par ligne), état (neuf, occasion, reconditionné).',
                'Le prix est facultatif (en FCFA) : vide, le site affiche « Prix sur demande » et le produit ne peut pas être commandé en ligne. La disponibilité se règle à la main ou par la synchronisation du stock.',
                'Sur la fiche publique, le visiteur peut demander le prix ou confirmer la disponibilité : sa demande arrive dans Demandes.',
            ]],
            ['bx bx-briefcase-alt-2', 'Présenter une réalisation', [
                'Réalisations → « Ajouter » : titre, résumé, contexte, besoin, solution, technologies, visuels, logiciel CSB utilisé.',
                'Ne publiez un projet qu\'avec l\'autorisation du client ; son nom est facultatif.',
            ]],
            ['bx bx-news', 'Rédiger une actualité', [
                'Actualités → « Rédiger » : titre, catégorie, texte en Markdown (onglet « Aperçu » pour voir la mise en forme), image de couverture.',
                'Laissez la date de publication vide pour publier tout de suite, ou choisissez une date future pour programmer l\'article.',
                'Le bouton « œil » de la liste montre l\'article tel qu\'il apparaîtra sur le site, même s\'il n\'est pas publié.',
            ]],
            ['bx bx-search-alt', 'Soigner le référencement', [
                'Chaque fiche a un bloc « Référencement » : titre (60 caractères), méta-description (160 caractères) et image de partage (aperçu WhatsApp, Facebook). Vides, des valeurs sont déduites du contenu.',
                'Changer l\'adresse d\'une fiche crée automatiquement une redirection : les anciens liens continuent de fonctionner.',
                'Le plan du site (sitemap.xml) se met à jour seul ; déclarez-le dans Google Search Console (code de vérification dans Pages et paramètres → Référencement).',
            ]],
            ['bx bx-list-plus', 'Saisir plusieurs éléments d\'un coup', [
                'Dans les listes (services, témoignages, catégories, matériel, logiciels, réalisations, abonnés, clients), « Saisie en lot / import » ouvre une grille : « Ajouter une ligne » pour en saisir plusieurs, ou collez des lignes copiées depuis Excel.',
                '« Modèle Excel » télécharge un fichier prêt à remplir (listes déroulantes, mode d\'emploi) ; « Importer un fichier » l\'envoie. Tout ou rien : la moindre erreur est signalée ligne par ligne et rien n\'est enregistré.',
            ]],
            ['bx bx-cart', 'Traiter les commandes de la boutique', [
                'Un matériel publié avec un prix (et pas « Indisponible ») affiche « Ajouter au panier » sur le site. Pour une promotion, renseignez « Prix promotionnel » (et une date de fin) dans sa fiche : l\'ancien prix s\'affiche barré.',
                'Chaque commande arrive « Nouvelle » (pastille, cloche, e-mail, WhatsApp si activé). Appelez le client pour confirmer la disponibilité, puis passez-la « Confirmée ».',
                'Quand l\'argent est reçu (espèces, Orange Money, Moov Money, Wave), passez-la « Payée » en notant la référence de la transaction, puis « Livrée / retirée ». Une annulation exige un motif. Le client reçoit un e-mail à chaque étape.',
                'Les numéros marchands affichés au client et les textes de retrait / livraison se règlent dans Pages et paramètres → Boutique. Les prix de chaque ligne sont figés au moment de la commande.',
            ]],
            ['bx bx-sync', 'Synchroniser le stock', [
                'Renseignez la « Référence » de chaque matériel : c\'est le code de l\'article dans votre logiciel de stock.',
                'Synchronisation du stock → « Modèle Excel », complétez les quantités (et les prix si besoin), puis « Synchroniser ». Quantité 0 : le produit passe « Indisponible » et ne peut plus être commandé.',
                'Pour une mise à jour automatique, l\'administrateur crée un jeton d\'API (affiché une seule fois) et le donne à votre logiciel de stock. Chaque synchronisation apparaît dans le journal, avec les références inconnues.',
            ]],
            ['bx bx-share-alt', 'Partenaires et commissions', [
                'Partenaires → « Ajouter » : nom, code du lien (son lien devient l\'adresse du site suivie de ?ref=code), taux de commission en % pour le matériel, les logiciels et le sur mesure, durée d\'attribution (30 jours par défaut), code promo facultatif (remise en % et date de fin).',
                'Pour qu\'il ait son espace : créez-lui un compte au rôle « Communication / réseaux sociaux » (Paramètres → Utilisateurs), puis choisissez ce compte sur sa fiche. Il voit ses résultats, ses commissions et le kit de publication ; il peut rédiger des actualités (publier reste une option à lui accorder).',
                'Une visite, une demande, une inscription ou une commande faite après un clic sur son lien (ou avec son code promo) lui est attribuée. Une commande de la boutique crée sa commission automatiquement quand vous la passez « Payée » ; une annulation l\'annule.',
                'Vente de logiciel ou projet sur mesure : sur la fiche de la demande venue de lui (encadré « Venue d\'un partenaire »), ou sur sa fiche avec « Enregistrer une vente », saisissez le montant encaissé : la commission est calculée avec son taux.',
                'Pour payer : cochez les commissions « À payer » sur sa fiche, indiquez la référence du transfert, puis « Marquer payées ».',
                'Kit de publication : pour chaque produit, logiciel, réalisation ou actualité, des textes prêts à copier (Facebook, Instagram, TikTok, WhatsApp, LinkedIn) avec son lien et son code, et des visuels prêts à poster (carré et story) générés automatiquement.',
            ]],
            ['bx bx-video', 'Témoignages en vidéo', [
                'Témoignages → « Ajouter » : nom, fonction, organisation, note, texte, photo, et une vidéo (envoyée depuis votre ordinateur ou téléphone, 300 Mo au plus) ou un lien YouTube / Facebook / TikTok.',
                'La vidéo envoyée est convertie en quelques minutes (format lisible sur tous les téléphones, image d\'aperçu, durée) : « Préparation… » s\'affiche en attendant.',
                'Vos clients peuvent envoyer eux-mêmes leur témoignage depuis la page « Partager mon expérience » du site : il arrive « À modérer » (pastille, cloche, e-mail, WhatsApp). Regardez-le, puis cliquez sur le bouton vert « Valider » (au téléphone : sous le nom de l\'auteur) : il est publié aussitôt. La pastille « À modérer » n\'affiche que ceux qui attendent. Leur téléphone ou e-mail n\'est jamais publié.',
                '« Mis en avant » place un témoignage en premier sur le site et l\'accueil.',
            ]],
            ['bx bx-support', 'Espace client et tickets', [
                'Les clients créent leur compte sur le site (icône personne en haut à droite) ; vous pouvez aussi les créer depuis Clients (une invitation leur est envoyée) et déposer des documents (devis, factures) dans leur espace.',
                'Un client ouvre un ticket d\'assistance depuis son espace. Tickets → fiche : « Envoyer au client » répond par e-mail ; cochez « Note interne » pour une information réservée à l\'équipe.',
                'Réglez le statut, la priorité et la personne qui suit le ticket. Si le client répond à un ticket résolu, il se rouvre automatiquement.',
            ]],
            ['bx bxl-whatsapp', 'Recevoir les alertes WhatsApp', [
                'Paramètres → Alertes WhatsApp : numéros à prévenir et événements (demandes, commandes, tickets). L\'envoi exige un compte WhatsApp Business (Meta) configuré sur le serveur.',
                '« Envoyer un message de test » vérifie la configuration ; les échecs d\'envoi sont listés sur le même écran.',
            ]],
            ['bx bx-user-circle', 'Gérer les comptes', [
                'Paramètres → Utilisateurs → « Ajouter » : nom, numéro de téléphone et rôle (Administrateur, Gestionnaire de contenu, Commercial).',
                'Sans mot de passe saisi, un mot de passe temporaire est généré et affiché une seule fois : transmettez-le à la personne, elle le changera à sa première connexion.',
                'Un compte qui n\'est plus utilisé se désactive (accès coupé immédiatement) ; un mot de passe oublié se réinitialise depuis la liste.',
            ]],
        ];
    @endphp

    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="mb-0 text-white"><i class='bx bx-help-circle me-2'></i>GUIDE D'UTILISATION DE L'ADMINISTRATION</h6>
        </div>
        <div class="card-body">
            <p class="text-muted">Ce guide reprend les tâches courantes de l'administration du site, dans l'ordre où on les découvre.</p>
            <div class="accordion" id="aide">
                @foreach ($chapters as $n => [$icon, $title, $steps])
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="aide-h{{ $n }}">
                            <button class="accordion-button {{ $n ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#aide-c{{ $n }}" aria-expanded="{{ $n ? 'false' : 'true' }}" aria-controls="aide-c{{ $n }}">
                                <i class='{{ $icon }} me-2'></i>{{ $n + 1 }}. {{ $title }}
                            </button>
                        </h2>
                        <div id="aide-c{{ $n }}" class="accordion-collapse collapse {{ $n ? '' : 'show' }}" aria-labelledby="aide-h{{ $n }}" data-bs-parent="#aide">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    @foreach ($steps as $step)
                                        <li class="mb-1">{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
