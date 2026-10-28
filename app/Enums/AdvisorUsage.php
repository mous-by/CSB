<?php

namespace App\Enums;

/**
 * Usage d'un ordinateur pour le conseiller d'achat (coché sur la fiche matériel par l'équipe). Les conseils sont écrits
 * pour un débutant ; `level` : 1 = ordinateur d'entrée de gamme suffisant, 3 = machine puissante. Textes À VALIDER AVEC CSB.
 */
enum AdvisorUsage: string
{
    case Internet = 'internet';
    case Bureautique = 'bureautique';
    case Etudes = 'etudes';
    case Gestion = 'gestion';
    case Programmation = 'programmation';
    case Creation = 'creation';
    case Jeux = 'jeux';

    public function label(): string
    {
        return match ($this) {
            self::Internet => 'Internet, réseaux sociaux, films',
            self::Bureautique => 'Bureautique (Word, Excel, courriers)',
            self::Etudes => 'Études (cours, exposés, mémoire)',
            self::Gestion => 'Comptabilité et logiciels de gestion',
            self::Programmation => 'Programmation et informatique',
            self::Creation => 'Graphisme, photo, montage vidéo',
            self::Jeux => 'Jeux vidéo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Internet => 'bx-globe',
            self::Bureautique => 'bx-file',
            self::Etudes => 'bx-book-open',
            self::Gestion => 'bx-calculator',
            self::Programmation => 'bx-code-alt',
            self::Creation => 'bx-palette',
            self::Jeux => 'bx-joystick',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::Internet, self::Bureautique, self::Etudes => 1,
            self::Gestion, self::Programmation => 2,
            self::Creation, self::Jeux => 3,
        };
    }

    /** Ce qu'il faut au minimum, en mots simples. */
    public function advice(): string
    {
        return match ($this) {
            self::Internet => '4 Go de mémoire suffisent, 8 Go pour plus de confort. Un disque SSD rend l\'ordinateur rapide dès l\'allumage.',
            self::Bureautique => '8 Go de mémoire (RAM), un disque SSD de 256 Go, un processeur Intel Core i3 ou AMD Ryzen 3 ou mieux.',
            self::Etudes => 'Un portable léger avec une bonne batterie (6 heures ou plus), 8 Go de mémoire et un disque SSD.',
            self::Gestion => '8 Go de mémoire au minimum (16 Go si plusieurs logiciels sont ouverts), un SSD, un Intel Core i5 ou AMD Ryzen 5. Prévoyez une sauvegarde de vos données.',
            self::Programmation => '16 Go de mémoire, un SSD de 512 Go, un Intel Core i5 / AMD Ryzen 5 récent ou mieux, et un écran confortable.',
            self::Creation => '16 Go de mémoire ou plus, une carte graphique dédiée, un SSD de 512 Go ou plus et un bon écran (Full HD, couleurs fidèles).',
            self::Jeux => 'Une carte graphique dédiée (NVIDIA GeForce ou AMD Radeon), 16 Go de mémoire, un Intel Core i5 / AMD Ryzen 5 ou mieux et un bon refroidissement.',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $u) => [$u->value => $u->label()])->all();
    }
}
