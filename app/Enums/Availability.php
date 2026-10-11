<?php

namespace App\Enums;

/** Disponibilité déclarée d'un matériel, mise à jour à la main en V1 (cahier §5.4). */
enum Availability: string
{
    case Disponible = 'disponible';
    case SurDemande = 'sur_demande';
    case Indisponible = 'indisponible';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::SurDemande => 'Sur demande',
            self::Indisponible => 'Indisponible',
        };
    }

    public function tag(): string
    {
        return match ($this) {
            self::Disponible => 'tag-success',
            self::SurDemande => 'tag-info',
            self::Indisponible => 'tag-danger',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Disponible => 'bg-success-subtle',
            self::SurDemande => 'bg-info-subtle',
            self::Indisponible => 'bg-danger-subtle',
        };
    }

    public function schema(): string
    {
        return match ($this) {
            self::Disponible => 'https://schema.org/InStock',
            self::SurDemande => 'https://schema.org/PreOrder',
            self::Indisponible => 'https://schema.org/OutOfStock',
        };
    }
}
