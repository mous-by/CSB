<?php

namespace App\Enums;

/** Retrait au magasin ou livraison. */
enum DeliveryMethod: string
{
    case Retrait = 'retrait';
    case Livraison = 'livraison';

    public function label(): string
    {
        return $this === self::Retrait ? 'Retrait au magasin CSB' : 'Livraison';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }
}
