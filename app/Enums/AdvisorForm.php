<?php

namespace App\Enums;

/** Format d'un ordinateur pour le conseiller d'achat. */
enum AdvisorForm: string
{
    case Portable = 'portable';
    case Bureau = 'bureau';
    case ToutEnUn = 'tout_en_un';

    public function label(): string
    {
        return match ($this) {
            self::Portable => 'Portable',
            self::Bureau => 'Ordinateur de bureau (tour + écran)',
            self::ToutEnUn => 'Tout-en-un (écran et ordinateur ensemble)',
        };
    }

    /** Explication pour un débutant. */
    public function hint(): string
    {
        return match ($this) {
            self::Portable => 'Se déplace partout et continue de marcher pendant une coupure de courant grâce à sa batterie.',
            self::Bureau => 'Reste au bureau ; plus puissant pour le même prix, facile à réparer et à faire évoluer. Prévoir un onduleur.',
            self::ToutEnUn => 'Un seul appareil posé sur le bureau, peu de câbles, idéal pour un accueil ou une secrétaire.',
        };
    }
}
