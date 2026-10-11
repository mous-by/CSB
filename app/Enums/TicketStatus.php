<?php

namespace App\Enums;

/** Avancement d'un ticket de support. */
enum TicketStatus: string
{
    case Ouvert = 'ouvert';
    case EnCours = 'en_cours';
    case AttenteClient = 'attente_client';
    case Resolu = 'resolu';
    case Ferme = 'ferme';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'Ouvert',
            self::EnCours => 'En cours',
            self::AttenteClient => 'En attente de votre réponse',
            self::Resolu => 'Résolu',
            self::Ferme => 'Fermé',
        };
    }

    /** Libellé côté équipe. */
    public function teamLabel(): string
    {
        return $this === self::AttenteClient ? 'Attente client' : $this->label();
    }

    public function badge(): string
    {
        return match ($this) {
            self::Ouvert => 'bg-danger-subtle',
            self::EnCours => 'bg-warning-subtle',
            self::AttenteClient => 'bg-info-subtle',
            self::Resolu => 'bg-success-subtle',
            self::Ferme => 'bg-secondary-subtle',
        };
    }

    public function tag(): string
    {
        return match ($this) {
            self::Ouvert, self::EnCours => 'tag-info',
            self::AttenteClient => 'tag-warning',
            self::Resolu => 'tag-success',
            self::Ferme => 'tag-muted',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolu, self::Ferme], true);
    }
}
