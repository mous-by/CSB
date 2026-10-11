<?php

namespace App\Enums;

/**
 * Familles de catégories (cahier §5.1 logiciels, §5.3 matériel, §4 actualités). Une catégorie appartient à une seule
 * famille ; son adresse est unique dans sa famille.
 */
enum CategoryType: string
{
    case Logiciel = 'logiciel';
    case Materiel = 'materiel';
    case Article = 'article';

    public function label(): string
    {
        return match ($this) {
            self::Logiciel => 'Logiciels',
            self::Materiel => 'Matériel',
            self::Article => 'Actualités',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Logiciel => 'bx bx-code-block',
            self::Materiel => 'bx bx-laptop',
            self::Article => 'bx bx-news',
        };
    }

    /** Permission qui gère les catégories de cette famille (celle du module de contenu concerné). */
    public function permission(): string
    {
        return match ($this) {
            self::Logiciel => 'logiciels.update',
            self::Materiel => 'materiels.update',
            self::Article => 'articles.update',
        };
    }

    /**
     * Catégories proposées par le cahier, créées par ContenuInitialSeeder.
     *
     * @return list<string>
     */
    public function defaults(): array
    {
        return match ($this) {
            self::Logiciel => ['Éducation et gestion scolaire', 'Commerce, vente et gestion de stock', 'Transport, réservation et logistique', 'Gestion d\'entreprise et solutions métiers', 'Applications web et mobiles sur mesure'],
            self::Materiel => ['Ordinateurs portables', 'Ordinateurs de bureau', 'Imprimantes', 'Accessoires', 'Réseau et composants'],
            self::Article => ['Actualités', 'Conseils', 'Annonces'],
        };
    }
}
