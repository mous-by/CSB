<?php

namespace App\Enums;

/** Traitement d'une demande (cahier §6.5). */
enum DemandeStatus: string
{
    case Nouvelle = 'nouvelle';
    case EnCours = 'en_cours';
    case DevisEnvoye = 'devis_envoye';
    case Gagnee = 'gagnee';
    case Perdue = 'perdue';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::EnCours => 'En cours',
            self::DevisEnvoye => 'Devis envoyé',
            self::Gagnee => 'Gagnée',
            self::Perdue => 'Perdue / clôturée',
        };
    }

    public function meaning(): string
    {
        return match ($this) {
            self::Nouvelle => 'Demande reçue et non encore traitée.',
            self::EnCours => 'Demande prise en charge.',
            self::DevisEnvoye => 'Une proposition a été communiquée au prospect.',
            self::Gagnee => 'Le prospect est devenu client pour cette demande.',
            self::Perdue => 'Demande non aboutie ou clôturée, avec motif facultatif.',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Nouvelle => 'bg-danger-subtle',
            self::EnCours => 'bg-warning-subtle',
            self::DevisEnvoye => 'bg-info-subtle',
            self::Gagnee => 'bg-success-subtle',
            self::Perdue => 'bg-secondary-subtle',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Nouvelle, self::EnCours, self::DevisEnvoye], true);
    }
}
