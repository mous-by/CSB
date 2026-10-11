<?php

namespace App\Enums;

/** Nature d'un ticket (services de CSB : réparation, maintenance, logiciels, réseaux). */
enum TicketCategory: string
{
    case Reparation = 'reparation';
    case Maintenance = 'maintenance';
    case Logiciel = 'logiciel';
    case Reseau = 'reseau';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Reparation => 'Réparation d\'un appareil',
            self::Maintenance => 'Maintenance',
            self::Logiciel => 'Assistance sur un logiciel',
            self::Reseau => 'Réseau / Internet',
            self::Autre => 'Autre',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
