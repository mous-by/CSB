<?php

namespace App\Enums;

/** Moyens de paiement de la boutique (paiement confirmé à la main par l'équipe, sans prestataire en ligne). */
enum PaymentMethod: string
{
    case Especes = 'especes';
    case OrangeMoney = 'orange_money';
    case MoovMoney = 'moov_money';
    case Wave = 'wave';

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces (au retrait ou à la livraison)',
            self::OrangeMoney => 'Orange Money',
            self::MoovMoney => 'Moov Money',
            self::Wave => 'Wave',
        };
    }

    /** Clé du réglage qui contient le numéro marchand (null pour les espèces). */
    public function settingKey(): ?string
    {
        return match ($this) {
            self::Especes => null,
            self::OrangeMoney => 'shop.orange_money',
            self::MoovMoney => 'shop.moov_money',
            self::Wave => 'shop.wave',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }
}
