<?php

/*
|--------------------------------------------------------------------------
| Site public de CSB
|--------------------------------------------------------------------------
|
| `defaults` : valeur de chaque paramètre du site tant qu'il n'a pas été modifié dans l'administration
| (Pages et paramètres, App\Support\SiteSettings). Coordonnées : cahier, Annexe A ; les textes marqués
| « À COMPLÉTER » attendent les informations de CSB (cahier §16). Ce sont des contenus publics destinés au site,
| pas des secrets.
|
*/

return [

    // Préfixe d'URL de l'administration (cahier §7 « URL dédiée »).
    'admin_path' => env('ADMIN_PATH', 'admin'),

    // Tunnel (ngrok) ou proxy HTTPS devant le site : « * » ou adresses séparées par des virgules ; vide = aucun.
    // Sans cela, les liens des CSS / JS / images sortent en http et le navigateur les bloque (contenu mixte).
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Destinataire des e-mails de notification des demandes (cahier §3.1, §6.4) ; vide = adresse « contact.email ».
    'notification_email' => env('SITE_NOTIFICATION_EMAIL'),

    'defaults' => [
        // Entreprise
        'company.name' => 'Computer Service Barry',
        'company.short_name' => 'CSB',
        'company.slogan' => 'Qualité • Fiabilité • Performance',
        'company.tagline' => 'Prestataire informatique et concepteur de solutions numériques',

        // Coordonnées (cahier, Annexe A)
        'contact.phone_1' => '74 74 56 69',
        'contact.phone_2' => '67 20 57 36',
        'contact.whatsapp' => '74 74 56 69',
        'contact.email' => 'barrymoustapha908@gmail.com',
        'contact.city' => 'Ségou, Mali',
        'contact.address' => '',
        'contact.hours' => '',
        'contact.map_url' => '',

        // Réseaux sociaux (cahier §7 « liens sociaux ») : vides tant que CSB ne les a pas fournis.
        'social.facebook' => '',
        'social.linkedin' => '',
        'social.instagram' => '',
        'social.tiktok' => '',
        'social.youtube' => '',

        // Accueil (maquette validée par CSB le 2026-10-11)
        'home.kicker' => 'Des solutions informatiques pour vous, avec passion',
        'home.hero_title' => 'Votre partenaire en solutions informatiques',
        'home.hero_subtitle' => 'Vente • Réparation • Développement • Conseil • Accompagnement',
        'home.hero_text' => 'Vente de matériel, réparation et maintenance, développement de logiciels et d\'applications web et mobiles : CSB accompagne les professionnels de bout en bout.',
        // Une ligne par point : icône Boxicons | titre | précision
        'home.hero_points' => "bx-laptop|Matériel informatique|neuf et occasion\nbx-wrench|Réparation & maintenance|toutes marques\nbx-code-alt|Développement web|et mobile sur mesure\nbx-shield-quarter|Réseaux, cybersécurité|et solutions informatiques",
        'home.values' => "bx-medal|Qualité|Des solutions fiables et durables\nbx-check-shield|Fiabilité|Un accompagnement professionnel\nbx-rocket|Performance|Des outils adaptés à vos besoins\nbx-group|Satisfaction client|Votre satisfaction, notre priorité !",
        'home.why_title' => "Pourquoi choisir CSB\u{00A0}?",
        'home.why_points' => "Une expertise reconnue\nDes solutions adaptées à vos besoins\nUn accompagnement personnalisé\nUn support technique réactif\nDes outils modernes et sécurisés",
        // Une ligne par chiffre : valeur | libellé | icône Boxicons (chiffres fournis par CSB)
        'home.stats' => "+50|Projets réalisés|bx-cog\n+100|Clients satisfaits|bx-group\n4|Solutions principales|bx-desktop\n3+|Années d'expérience|bx-trophy",
        // Images (identifiant d'une image de la bibliothèque) ; vide = visuel fourni avec le site.
        'site.logo' => '',
        'home.hero_image' => '',
        'home.why_image' => '',

        // À propos (textes officiels à fournir par CSB, cahier §16)
        'about.presentation' => "Computer Service Barry (CSB) est une entreprise de services informatiques : vente de matériel, réparation et maintenance, développement de logiciels et d'applications web et mobiles, et accompagnement informatique des professionnels.",
        'about.mission' => 'À COMPLÉTER — mission de CSB.',
        'about.vision' => 'À COMPLÉTER — vision de CSB.',
        'about.values' => "Qualité\nFiabilité\nPerformance",
        'about.method' => "Écouter et analyser le besoin\nProposer une solution adaptée et chiffrée\nRéaliser, tester et livrer\nAccompagner et maintenir dans la durée",

        // Pied de page
        'footer.text' => 'Vente de matériel, maintenance, développement de logiciels et accompagnement informatique des professionnels.',

        // Pages légales (Markdown) : à adapter à l'identité juridique de CSB (cahier §10, §16).
        'legal.mentions' => "## Éditeur du site\n\nComputer Service Barry (CSB).\n\nÀ COMPLÉTER : forme juridique, numéro d'immatriculation (RCCM), NIF, adresse du siège, directeur de la publication.\n\n## Hébergement\n\nÀ COMPLÉTER : nom et adresse de l'hébergeur.\n\n## Propriété intellectuelle\n\nLes contenus de ce site (textes, images, logos) sont la propriété de CSB ou de leurs auteurs. Toute reproduction sans autorisation est interdite.",
        'legal.privacy' => "## Données collectées\n\nLes formulaires du site (contact, devis, démonstration, disponibilité) collectent uniquement les informations nécessaires pour vous répondre : nom, organisation, coordonnées, message et, le cas échéant, la pièce jointe que vous envoyez.\n\n## Utilisation\n\nCes données servent exclusivement à traiter votre demande et à vous recontacter. Elles ne sont ni vendues ni cédées à des tiers.\n\n## Conservation\n\nÀ COMPLÉTER : durée de conservation des demandes.\n\n## Vos droits\n\nVous pouvez demander l'accès, la rectification ou la suppression de vos données en écrivant à l'adresse e-mail indiquée sur la page Contact.",

        // Boutique : numéros marchands à fournir par CSB ; vides, ils sont communiqués lors de l'appel de confirmation.
        'shop.orange_money' => '',
        'shop.moov_money' => '',
        'shop.wave' => '',
        'shop.pickup_text' => 'Retrait au magasin CSB à Ségou, après notre appel de confirmation.',
        'shop.delivery_text' => 'Livraison à Ségou et dans les environs ; frais selon la zone, confirmés par téléphone avant l\'envoi.',

        // Alertes WhatsApp (destinataires à fournir par CSB ; l'envoi exige aussi WHATSAPP_TOKEN dans .env).
        'whatsapp.recipients' => '',
        'whatsapp.events' => "demandes\ncommandes\ntickets\ntemoignages",

        // Référencement (cahier §11.1)
        'seo.default_description' => 'Computer Service Barry (CSB) : vente de matériel informatique, réparation et maintenance, développement de logiciels et d\'applications web et mobiles.',
        'seo.google_site_verification' => '',
    ],

];
