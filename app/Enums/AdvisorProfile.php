<?php

namespace App\Enums;

/** Métier ou situation du visiteur (première question du conseiller d'achat) et usages qu'il implique. */
enum AdvisorProfile: string
{
    case Eleve = 'eleve';
    case Enseignant = 'enseignant';
    case Bureau = 'bureau';
    case Comptable = 'comptable';
    case Informaticien = 'informaticien';
    case Createur = 'createur';
    case Joueur = 'joueur';
    case Famille = 'famille';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Eleve => 'Élève ou étudiant',
            self::Enseignant => 'Enseignant ou formateur',
            self::Bureau => 'Secrétariat, administration',
            self::Comptable => 'Comptable, gestionnaire, commerçant',
            self::Informaticien => 'Développeur, informaticien',
            self::Createur => 'Graphiste, photographe, vidéaste',
            self::Joueur => 'Joueur',
            self::Famille => 'Usage personnel ou familial',
            self::Autre => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Eleve => 'bx-book-reader',
            self::Enseignant => 'bx-chalkboard',
            self::Bureau => 'bx-briefcase',
            self::Comptable => 'bx-calculator',
            self::Informaticien => 'bx-code-alt',
            self::Createur => 'bx-palette',
            self::Joueur => 'bx-joystick',
            self::Famille => 'bx-home-heart',
            self::Autre => 'bx-dots-horizontal-rounded',
        };
    }

    /** @return list<AdvisorUsage> */
    public function usages(): array
    {
        return match ($this) {
            self::Eleve => [AdvisorUsage::Etudes, AdvisorUsage::Bureautique, AdvisorUsage::Internet],
            self::Enseignant => [AdvisorUsage::Bureautique, AdvisorUsage::Etudes, AdvisorUsage::Internet],
            self::Bureau => [AdvisorUsage::Bureautique, AdvisorUsage::Internet],
            self::Comptable => [AdvisorUsage::Gestion, AdvisorUsage::Bureautique],
            self::Informaticien => [AdvisorUsage::Programmation, AdvisorUsage::Bureautique],
            self::Createur => [AdvisorUsage::Creation],
            self::Joueur => [AdvisorUsage::Jeux],
            self::Famille => [AdvisorUsage::Internet, AdvisorUsage::Bureautique],
            self::Autre => [],
        };
    }
}
