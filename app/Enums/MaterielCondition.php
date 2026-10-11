<?php

namespace App\Enums;

/** État d'un matériel, toujours clairement indiqué (cahier §5.3, §5.4). */
enum MaterielCondition: string
{
    case Neuf = 'neuf';
    case Occasion = 'occasion';
    case Reconditionne = 'reconditionne';

    public function label(): string
    {
        return match ($this) {
            self::Neuf => 'Neuf',
            self::Occasion => 'Occasion',
            self::Reconditionne => 'Reconditionné',
        };
    }

    public function tag(): string
    {
        return match ($this) {
            self::Neuf => 'tag-success',
            self::Occasion => 'tag-warning',
            self::Reconditionne => 'tag-info',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Neuf => 'bg-success-subtle',
            self::Occasion => 'bg-warning-subtle',
            self::Reconditionne => 'bg-info-subtle',
        };
    }

    /** Valeur schema.org (données structurées). */
    public function schema(): string
    {
        return match ($this) {
            self::Neuf => 'https://schema.org/NewCondition',
            self::Occasion => 'https://schema.org/UsedCondition',
            self::Reconditionne => 'https://schema.org/RefurbishedCondition',
        };
    }
}
