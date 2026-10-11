<?php

namespace App\Enums;

/** Avancement d'une commande de la boutique. */
enum CommandeStatus: string
{
    case Nouvelle = 'nouvelle';
    case Confirmee = 'confirmee';
    case Payee = 'payee';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::Confirmee => 'Confirmée',
            self::Payee => 'Payée',
            self::Livree => 'Livrée / retirée',
            self::Annulee => 'Annulée',
        };
    }

    /** Ce que le statut signifie pour le client. */
    public function meaning(): string
    {
        return match ($this) {
            self::Nouvelle => 'Commande reçue : nous vérifions la disponibilité et vous appelons pour confirmer.',
            self::Confirmee => 'Disponibilité confirmée : vous pouvez régler votre commande.',
            self::Payee => 'Paiement reçu : votre commande est en préparation.',
            self::Livree => 'Commande livrée ou retirée. Merci de votre confiance !',
            self::Annulee => 'Commande annulée.',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Nouvelle => 'bg-danger-subtle',
            self::Confirmee => 'bg-warning-subtle',
            self::Payee => 'bg-info-subtle',
            self::Livree => 'bg-success-subtle',
            self::Annulee => 'bg-secondary-subtle',
        };
    }

    public function tag(): string
    {
        return match ($this) {
            self::Nouvelle, self::Confirmee => 'tag-info',
            self::Payee => 'tag-warning',
            self::Livree => 'tag-success',
            self::Annulee => 'tag-muted',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Livree, self::Annulee], true);
    }
}
