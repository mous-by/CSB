<?php

namespace App\Enums;

/**
 * Les rôles de la V1 (cahier §7.1, CLAUDE.md §4.3). Un utilisateur a exactement un rôle.
 * Niveaux et plafond de rôle vivent dans config/role_hierarchy.php.
 */
enum RoleName: string
{
    case Superadmin = 'superadmin';
    case Administrateur = 'administrateur';
    case Gestionnaire = 'gestionnaire';
    case Commercial = 'commercial';
    // Partenaire de diffusion sur les réseaux sociaux (demande de CSB, 2026-10-12) : son espace, son kit, des actualités.
    case Communication = 'communication';
    // Administrateur de l'application KalanNet (demande de CSB, 2026-11-10) : traite les tickets venus de KalanNet, rien d'autre.
    case AdminKalanNet = 'admin_kalannet';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Administrateur => 'Administrateur',
            self::Gestionnaire => 'Gestionnaire de contenu',
            self::Commercial => 'Commercial',
            self::Communication => 'Communication / réseaux sociaux',
            self::AdminKalanNet => 'Administrateur KalanNet',
        };
    }

    /**
     * Application connectée dont ce rôle traite les tickets, et seulement ceux-là (nom du jeton, `tickets.source_app`).
     * Null : le rôle n'est pas limité à une application.
     */
    public function ticketApp(): ?string
    {
        return match ($this) {
            self::AdminKalanNet => 'KalanNet',
            default => null,
        };
    }

    public function level(): int
    {
        return (int) config("role_hierarchy.levels.{$this->value}", 0);
    }

    /**
     * Rôles que ce rôle peut créer et gérer (plafond de rôle).
     *
     * @return list<self>
     */
    public function manages(): array
    {
        return array_map(self::from(...), config("role_hierarchy.manages.{$this->value}", []));
    }

    public function canManage(self $target): bool
    {
        return in_array($target, $this->manages(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
