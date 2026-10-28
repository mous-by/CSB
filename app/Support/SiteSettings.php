<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Paramètres du site public, modifiables depuis l'administration (Pages et paramètres, cahier §7) : coordonnées,
 * slogan, liens sociaux, textes de l'accueil, de « À propos », du pied de page et des pages légales. Une valeur jamais
 * enregistrée reprend sa valeur par défaut (config/site.php). Chaque modification est auditée par AppSetting.
 */
class SiteSettings
{
    private const CACHE_KEY = 'app_settings';

    /**
     * Champs du formulaire, rangés par onglet : clé => [libellé, type, longueur max, aide]. `permission` : contenus des
     * pages (`pages.update`, gestionnaire de contenu) ou paramètres sensibles (`pages.settings`, cahier §7.1).
     * Types : text, tels (un ou plusieurs numéros séparés par « / »), textarea (une ligne = un élément de liste), markdown, email, tel, url, image (image de la bibliothèque).
     *
     * @var array<string, array{label: string, permission: string, icon: string, fields: array<string, array{0: string, 1: string, 2: int, 3?: string}>}>
     */
    public const GROUPS = [
        'entreprise' => ['label' => 'Entreprise', 'permission' => 'pages.settings', 'icon' => 'bx bx-buildings', 'fields' => [
            'company.name' => ['Raison sociale', 'text', 150],
            'company.short_name' => ['Sigle', 'text', 20],
            'company.slogan' => ['Slogan', 'text', 150],
            'company.tagline' => ['Accroche', 'text', 200, 'Phrase courte affichée sous le nom (titre des pages, partage).'],
            'site.logo' => ['Logo', 'image', 0, 'Logo affiché dans l\'en-tête et le pied de page (PNG transparent conseillé). Vide : logo CSB fourni avec le site.'],
            'footer.text' => ['Texte du pied de page', 'textarea', 400],
        ]],
        'coordonnees' => ['label' => 'Coordonnées', 'permission' => 'pages.settings', 'icon' => 'bx bx-phone', 'fields' => [
            'contact.phone_1' => ['Téléphone principal', 'tel', 30],
            'contact.phone_2' => ['Second téléphone', 'tel', 30],
            'contact.whatsapp' => ['Numéro WhatsApp', 'tel', 30, 'Numéro ouvert par le bouton WhatsApp du site (indicatif +223 ajouté aux numéros à 8 chiffres).'],
            'contact.email' => ['E-mail de contact', 'email', 150],
            'contact.city' => ['Ville affichée', 'text', 100, 'Affichée dans la barre du haut et le pied de page (ex. « Ségou, Mali »).'],
            'contact.address' => ['Adresse publique', 'textarea', 300, 'Laissée vide, elle n\'apparaît pas sur le site.'],
            'contact.hours' => ['Horaires', 'textarea', 300, 'Une ligne par plage, ex. « Lundi – vendredi : 8 h – 18 h ».'],
            'contact.map_url' => ['Lien de localisation', 'url', 500, 'Lien (Google Maps, OpenStreetMap…) ouvert dans un nouvel onglet ; aucune carte n\'est chargée dans la page.'],
        ]],
        'reseaux' => ['label' => 'Réseaux sociaux', 'permission' => 'pages.settings', 'icon' => 'bx bx-share-alt', 'fields' => [
            'social.facebook' => ['Facebook', 'url', 300],
            'social.linkedin' => ['LinkedIn', 'url', 300],
            'social.instagram' => ['Instagram', 'url', 300],
            'social.tiktok' => ['TikTok', 'url', 300],
            'social.youtube' => ['YouTube', 'url', 300],
        ]],
        'accueil' => ['label' => 'Accueil', 'permission' => 'pages.update', 'icon' => 'bx bx-home', 'fields' => [
            'home.kicker' => ['Surtitre', 'text', 120, 'Petite ligne au-dessus du titre.'],
            'home.hero_title' => ['Titre principal', 'text', 150, 'Le dernier mot s\'affiche en orange.'],
            'home.hero_subtitle' => ['Sous-titre', 'text', 200],
            'home.hero_text' => ['Texte d\'introduction (référencement)', 'textarea', 500, 'Utilisé pour la description de la page d\'accueil.'],
            'home.hero_image' => ['Image du bandeau', 'image', 0, 'Matériel informatique sur fond transparent (PNG ou WebP). Vide : illustration fournie avec le site.'],
            'home.hero_points' => ['Points à droite du bandeau', 'textarea', 1000, 'Une ligne par point : icône | titre | précision (ex. « bx-wrench|Réparation & maintenance|toutes marques »).'],
            'home.values' => ['Bande des valeurs', 'textarea', 1000, 'Une ligne par valeur : icône | titre | texte.'],
            'home.why_title' => ['Titre « Pourquoi CSB »', 'text', 120],
            'home.why_points' => ['Arguments « Pourquoi CSB »', 'textarea', 1000, 'Un argument par ligne.'],
            'home.why_image' => ['Photo « Pourquoi CSB »', 'image', 0],
            'home.stats' => ['Chiffres clés', 'textarea', 600, 'Une ligne par chiffre : valeur | libellé | icône (ex. « +50|Projets réalisés|bx-cog »). Uniquement des chiffres vérifiés. Chiffres automatiques : {visiteurs} (visites du site), {clients} (comptes clients), {realisations} (réalisations publiées), ex. « {visiteurs}|Visites du site|bx-show ».'],
        ]],
        'a-propos' => ['label' => 'À propos', 'permission' => 'pages.update', 'icon' => 'bx bx-info-circle', 'fields' => [
            'about.presentation' => ['Présentation de l\'entreprise', 'markdown', 5000],
            'about.mission' => ['Mission', 'textarea', 1000],
            'about.vision' => ['Vision', 'textarea', 1000],
            'about.values' => ['Valeurs', 'textarea', 1000, 'Une valeur par ligne.'],
            'about.method' => ['Méthode de travail', 'textarea', 1500, 'Une étape par ligne.'],
        ]],
        'legal' => ['label' => 'Pages légales', 'permission' => 'pages.update', 'icon' => 'bx bx-shield-quarter', 'fields' => [
            'legal.mentions' => ['Mentions légales', 'markdown', 20000],
            'legal.privacy' => ['Politique de confidentialité', 'markdown', 20000],
        ]],
        'boutique' => ['label' => 'Boutique', 'permission' => 'pages.settings', 'icon' => 'bx bx-cart', 'fields' => [
            'shop.orange_money' => ['Numéro(s) Orange Money', 'tels', 100, 'Affiché au client après sa commande pour le paiement. Plusieurs numéros : séparez-les par « / » (ex. 74 74 56 69 / 67 20 57 36). Vide : communiqué lors de l\'appel de confirmation.'],
            'shop.moov_money' => ['Numéro(s) Moov Money', 'tels', 100, 'Plusieurs numéros : séparez-les par « / ».'],
            'shop.wave' => ['Numéro(s) Wave', 'tels', 100, 'Plusieurs numéros : séparez-les par « / ».'],
            'shop.pickup_text' => ['Retrait au magasin', 'textarea', 300, 'Où et quand retirer une commande.'],
            'shop.delivery_text' => ['Livraison', 'textarea', 300, 'Zones et frais de livraison (confirmés par téléphone).'],
        ]],
        'seo' => ['label' => 'Référencement', 'permission' => 'pages.settings', 'icon' => 'bx bx-search-alt', 'fields' => [
            'seo.default_description' => ['Méta-description par défaut', 'textarea', 300, 'Utilisée par les pages qui n\'ont pas la leur (160 caractères conseillés).'],
            'seo.google_site_verification' => ['Code de vérification Google Search Console', 'text', 100, 'Seulement le code (contenu de la balise « google-site-verification »).'],
        ]],
    ];

    public function get(string $key): string
    {
        $stored = $this->stored();

        return (string) (array_key_exists($key, $stored) ? $stored[$key] : $this->default($key));
    }

    /** Valeur découpée en lignes non vides (valeurs, étapes de méthode, horaires). @return list<string> */
    public function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $this->get($key)) ?: [])));
    }

    /**
     * Lignes « a | b | c » découpées (points du bandeau, valeurs, chiffres).
     *
     * @return list<list<string>>
     */
    public function rows(string $key, int $columns): array
    {
        return array_map(fn ($line) => array_pad(array_map('trim', explode('|', $line, $columns)), $columns, ''), $this->lines($key));
    }

    /** URL d'une image réglée (image de la bibliothèque), sinon du visuel fourni avec le site. */
    public function imageUrl(string $key, string $fallback): string
    {
        $id = (int) $this->get($key);
        $media = $id ? Media::find($id) : null;

        return $media ? $media->url() : asset($fallback);
    }

    /** Valeur Markdown rendue en HTML sûr (HTML brut et liens dangereux refusés). */
    public function html(string $key): string
    {
        return Markdown::toHtml($this->get($key));
    }

    /**
     * Liens sociaux renseignés : réseau => URL.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return collect(self::GROUPS['reseaux']['fields'])->keys()
            ->mapWithKeys(fn ($key) => [Str::after($key, 'social.') => $this->get($key)])
            ->filter()
            ->all();
    }

    /** Lien d'appel téléphonique (tel:+223…), ou null. */
    public function telLink(string $key): ?string
    {
        $number = PhoneNumber::normalize($this->get($key));

        return $number ? 'tel:'.$number : null;
    }

    /** Lien WhatsApp (wa.me) avec un message prérempli facultatif, ou null si aucun numéro valide. */
    public function whatsappLink(?string $message = null): ?string
    {
        $number = PhoneNumber::normalize($this->get('contact.whatsapp'));
        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.ltrim($number, '+').($message ? '?text='.rawurlencode($message) : '');
    }

    /** Partage d'un texte sur WhatsApp (le visiteur choisit le destinataire). */
    public function whatsappShareLink(string $text): string
    {
        return 'https://wa.me/?text='.rawurlencode($text);
    }

    /** Destinataire des notifications de demandes. */
    public function notificationEmail(): ?string
    {
        return config('site.notification_email') ?: ($this->get('contact.email') ?: null);
    }

    /**
     * Enregistre des valeurs (clé => valeur). Une valeur égale au défaut supprime la surcharge. Audité par AppSetting.
     *
     * @param  array<string, ?string>  $values
     */
    public function save(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                $value = $value === null ? '' : str_replace("\r\n", "\n", $value);
                $setting = AppSetting::firstWhere('key', $key);

                if ($value === $this->default($key)) {
                    $setting?->delete();

                    continue;
                }

                $setting ? $setting->update(['value' => $value]) : AppSetting::create(['key' => $key, 'value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    public function default(string $key): string
    {
        // Les clés contiennent des points (« contact.email ») : lecture directe du tableau, pas en notation pointée.
        return (string) (config('site.defaults', [])[$key] ?? '');
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return collect(self::GROUPS)->flatMap(fn ($group) => array_keys($group['fields']))->values()->all();
    }

    /**
     * Valeurs enregistrées en base (cache vidé à chaque enregistrement). Base non migrée : aucune surcharge.
     *
     * @return array<string, ?string>
     */
    private function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('app_settings') ? AppSetting::pluck('value', 'key')->all() : []);
        } catch (Throwable) {
            return [];
        }
    }
}
