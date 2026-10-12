<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case APayer = 'a_payer';
    case Payee = 'payee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::APayer => 'À payer',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::APayer => 'bg-warning-subtle',
            self::Payee => 'bg-success-subtle',
            self::Annulee => 'bg-secondary-subtle',
        };
    }
}
