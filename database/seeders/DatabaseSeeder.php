<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Rôles et permissions (RoleSeeder), puis les superadmins déclarés dans .env (SuperadminSeeder, D27). Aucun
     * compte ni mot de passe dans Git ; `php artisan app:create-superadmin` reste disponible (mot de passe interactif).
     * Puis le contenu initial du site (ContenuInitialSeeder : services cités par le cahier, idempotent) et le contenu réel
     * de CSB (ContenuCsbSeeder : logiciels, matériel, réalisations avec leurs images, idempotent ; il fait partie du site
     * hébergé). Les données de test (DonneesTestSeeder) ne sont JAMAIS appelées ici.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, SuperadminSeeder::class, ContenuInitialSeeder::class, ContenuCsbSeeder::class]);
    }
}
