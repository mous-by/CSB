<?php

namespace App\Enums;

/** Type de vente qui rapporte une commission (un taux par type, réglé pour chaque partenaire). */
enum CommissionKind: string
{
    case Materiel = 'materiel';
    case Logiciel = 'logiciel';
    case SurMesure = 'sur_mesure';

    public function label(): string
    {
        return match ($this) {
            self::Materiel => 'Matériel informatique',
            self::Logiciel => 'Logiciel',
            self::SurMesure => 'Projet sur mesure',
        };
    }

    /** Colonne du taux sur la fiche du partenaire. */
    public function rateColumn(): string
    {
        return 'rate_'.$this->value;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $k) => [$k->value => $k->label()])->all();
    }
}
