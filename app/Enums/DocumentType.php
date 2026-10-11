<?php

namespace App\Enums;

/** Nature d'un document mis à disposition d'un client. */
enum DocumentType: string
{
    case Devis = 'devis';
    case Facture = 'facture';
    case Contrat = 'contrat';
    case Livrable = 'livrable';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Devis => 'Devis',
            self::Facture => 'Facture',
            self::Contrat => 'Contrat',
            self::Livrable => 'Livrable',
            self::Autre => 'Autre document',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Devis => 'bx-file',
            self::Facture => 'bx-receipt',
            self::Contrat => 'bx-pen',
            self::Livrable => 'bx-package',
            self::Autre => 'bx-paperclip',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
