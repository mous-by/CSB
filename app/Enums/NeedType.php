<?php

namespace App\Enums;

/** Type de besoin d'une demande de devis (cahier §6.3). */
enum NeedType: string
{
    case Logiciel = 'logiciel';
    case SurMesure = 'sur_mesure';
    case Materiel = 'materiel';
    case Maintenance = 'maintenance';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Logiciel => 'Logiciel',
            self::SurMesure => 'Développement sur mesure',
            self::Materiel => 'Matériel',
            self::Maintenance => 'Maintenance / réparation',
            self::Autre => 'Autre',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $n) => [$n->value => $n->label()])->all();
    }
}
