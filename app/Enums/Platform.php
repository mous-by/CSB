<?php

namespace App\Enums;

/**
 * Plateformes d'un logiciel (cahier §5.2 « Web, Android, iOS ou autre, uniquement si réellement disponible »).
 */
enum Platform: string
{
    case Web = 'web';
    case Android = 'android';
    case Ios = 'ios';
    case Windows = 'windows';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Android => 'Android',
            self::Ios => 'iOS',
            self::Windows => 'Windows',
            self::Autre => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Web => 'bx-globe',
            self::Android => 'bxl-android',
            self::Ios => 'bxl-apple',
            self::Windows => 'bxl-windows',
            self::Autre => 'bx-devices',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $p) => [$p->value => $p->label()])->all();
    }
}
