<?php

namespace App\Enums;

/** Urgence d'un ticket (réglée par le client à l'ouverture, ajustée par l'équipe). */
enum TicketPriority: string
{
    case Basse = 'basse';
    case Normale = 'normale';
    case Haute = 'haute';
    case Urgente = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Basse => 'Basse',
            self::Normale => 'Normale',
            self::Haute => 'Haute',
            self::Urgente => 'Urgente (activité bloquée)',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Basse => 'bg-secondary-subtle',
            self::Normale => 'bg-info-subtle',
            self::Haute => 'bg-warning-subtle',
            self::Urgente => 'bg-danger-subtle',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $p) => [$p->value => $p->label()])->all();
    }
}
