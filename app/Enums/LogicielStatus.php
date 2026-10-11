<?php

namespace App\Enums;

/**
 * Statut contrôlé d'un logiciel (cahier §5.1) : les fonctionnalités annoncées doivent correspondre à l'état réel.
 */
enum LogicielStatus: string
{
    case Disponible = 'disponible';
    case SurDemande = 'sur_demande';
    case EnDeveloppement = 'en_developpement';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::SurDemande => 'Sur demande',
            self::EnDeveloppement => 'En développement',
            self::Archive => 'Archivé',
        };
    }

    /** Classe de pastille du site public (site.css) et de l'administration. */
    public function tag(): string
    {
        return match ($this) {
            self::Disponible => 'tag-success',
            self::SurDemande => 'tag-info',
            self::EnDeveloppement => 'tag-warning',
            self::Archive => 'tag-muted',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Disponible => 'bg-success-subtle',
            self::SurDemande => 'bg-info-subtle',
            self::EnDeveloppement => 'bg-warning-subtle',
            self::Archive => 'bg-secondary-subtle',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
