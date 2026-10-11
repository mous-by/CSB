<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Prestation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Contenu initial du site, utilisable en production et idempotent : les services cités par le cahier (§1, §4) et les
 * catégories de logiciels, de matériel et d'actualités (§4, §5.1, §5.3, CategoryType::defaults()).
 * Textes génériques, sans promesse inventée : à relire et compléter par CSB (cahier §16 « liste des services et
 * descriptions commerciales approuvées »). Un service déjà présent (même adresse) n'est pas modifié.
 */
class ContenuInitialSeeder extends Seeder
{
    /** [titre, icône, résumé] */
    public const PRESTATIONS = [
        ['Vente de matériel informatique', 'bx-laptop', 'Ordinateurs de bureau et portables, imprimantes, consommables, accessoires et équipements réseau, neufs ou d\'occasion.'],
        ['Réparation et maintenance', 'bx-wrench', 'Diagnostic, dépannage et entretien de vos ordinateurs, imprimantes et périphériques.'],
        ['Développement de logiciels', 'bx-code-alt', 'Logiciels de gestion et solutions métiers conçus selon les besoins de votre activité.'],
        ['Applications web et mobiles', 'bx-mobile-alt', 'Sites et applications web et mobiles sur mesure, de la conception à la mise en ligne.'],
        ['Réseaux informatiques', 'bx-network-chart', 'Installation et configuration de réseaux pour vos bureaux.'],
        ['Accompagnement informatique', 'bx-support', 'Conseil et accompagnement des professionnels dans leurs choix et leurs projets informatiques.'],
    ];

    public function run(): void
    {
        foreach (CategoryType::cases() as $type) {
            foreach ($type->defaults() as $order => $name) {
                if (! Category::withTrashed()->ofType($type)->where('name', $name)->exists()) {
                    Category::create(['type' => $type, 'name' => $name, 'sort_order' => ($order + 1) * 10]);
                }
            }
        }

        foreach (self::PRESTATIONS as $order => [$title, $icon, $summary]) {
            $slug = Str::slug($title);
            if (Prestation::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }

            Prestation::create(['title' => $title, 'slug' => $slug, 'icon' => $icon, 'summary' => $summary, 'sort_order' => ($order + 1) * 10, 'is_published' => true]);
        }
    }
}
