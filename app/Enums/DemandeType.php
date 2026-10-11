<?php

namespace App\Enums;

/** Nature d'une demande reçue par le site (cahier §6.2 à §6.4). */
enum DemandeType: string
{
    case Contact = 'contact';
    case Devis = 'devis';
    case Demonstration = 'demonstration';
    case Disponibilite = 'disponibilite';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact',
            self::Devis => 'Demande de devis',
            self::Demonstration => 'Demande de démonstration',
            self::Disponibilite => 'Prix / disponibilité',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Contact => 'bx bx-envelope',
            self::Devis => 'bx bx-file',
            self::Demonstration => 'bx bx-desktop',
            self::Disponibilite => 'bx bx-purchase-tag',
        };
    }
}
