<?php

namespace App\Enums;

use App\Models\Materiel;

/** Tranche de budget du conseiller d'achat, en FCFA. */
enum AdvisorBudget: string
{
    case Moins150 = 'moins-150';
    case De150a250 = '150-250';
    case De250a400 = '250-400';
    case De400a600 = '400-600';
    case Plus600 = 'plus-600';
    case Inconnu = 'inconnu';

    public function min(): ?int
    {
        return match ($this) {
            self::De150a250 => 150000, self::De250a400 => 250000, self::De400a600 => 400000, self::Plus600 => 600000,
            default => null,
        };
    }

    public function max(): ?int
    {
        return match ($this) {
            self::Moins150 => 150000, self::De150a250 => 250000, self::De250a400 => 400000, self::De400a600 => 600000,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Moins150 => 'Moins de '.Materiel::money(150000),
            self::Plus600 => 'Plus de '.Materiel::money(600000),
            self::Inconnu => 'Je ne sais pas encore',
            default => Materiel::money($this->min()).' à '.Materiel::money($this->max()),
        };
    }
}
