<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Métadonnées d'une page publique (cahier §11.1) : titre et méta-description uniques, URL canonique, aperçu de partage
 * (Open Graph, WhatsApp), indexation. Construit par les contrôleurs du site, lu par layouts/site.blade.php.
 */
final class Seo
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd  données structurées supplémentaires (schema.org)
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly ?string $image = null,
        public readonly string $type = 'website',
        public readonly bool $index = true,
        public readonly array $jsonLd = [],
    ) {}

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public static function make(?string $title = null, ?string $description = null, ?string $image = null, ?string $canonical = null, string $type = 'website', bool $index = true, array $jsonLd = []): self
    {
        $settings = app(SiteSettings::class);
        $company = $settings->get('company.short_name') ?: 'CSB';
        $fullTitle = $title ? "{$title} | {$company}" : $settings->get('company.name').' — '.$settings->get('company.slogan');

        return new self(
            Str::limit(trim($fullTitle), 70, '…'),
            Str::limit(trim((string) ($description ?: $settings->get('seo.default_description'))), 170, '…'),
            $canonical ?? url()->current(),
            $image,
            $type,
            $index,
            $jsonLd,
        );
    }

    /**
     * Données structurées de l'entreprise (schema.org Organization), sur toutes les pages. Aucun avis ni prix inventé
     * (cahier §11.1).
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $settings = app(SiteSettings::class);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings->get('company.name'),
            'alternateName' => $settings->get('company.short_name') ?: null,
            'slogan' => $settings->get('company.slogan') ?: null,
            'url' => route('site.home'),
            'email' => $settings->get('contact.email') ?: null,
            'telephone' => PhoneNumber::normalize($settings->get('contact.phone_1')),
            'sameAs' => array_values($settings->socialLinks()) ?: null,
        ]);
    }

    /** Variante non indexée (formulaires, confirmations, résultats filtrés). */
    public function noIndex(): self
    {
        return new self($this->title, $this->description, $this->canonical, $this->image, $this->type, false, $this->jsonLd);
    }
}
