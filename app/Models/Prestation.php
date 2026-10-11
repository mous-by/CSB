<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\PrestationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Service proposé par CSB (page Services, cahier §4). Masqué = invisible sur le site, conservé dans l'administration.
 */
#[Fillable(['title', 'slug', 'icon', 'summary', 'description', 'sort_order', 'is_published'])]
class Prestation extends Model
{
    /** @use HasFactory<PrestationFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /** Icônes proposées dans le formulaire (Boxicons, servies localement). */
    public const ICONS = [
        'bx-laptop' => 'Ordinateur', 'bx-printer' => 'Imprimante', 'bx-wrench' => 'Réparation', 'bx-cog' => 'Maintenance',
        'bx-code-alt' => 'Développement', 'bx-mobile-alt' => 'Application mobile', 'bx-globe' => 'Site web', 'bx-network-chart' => 'Réseau',
        'bx-wifi' => 'Wi-Fi', 'bx-server' => 'Serveur', 'bx-shield-quarter' => 'Sécurité', 'bx-data' => 'Données',
        'bx-support' => 'Assistance', 'bx-chalkboard' => 'Formation', 'bx-bulb' => 'Conseil', 'bx-cart' => 'Vente',
        'bx-camera' => 'Vidéosurveillance', 'bx-check-circle' => 'Générique',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function activityNoun(): string
    {
        return 'Service';
    }

    public function activityLabel(): string
    {
        return (string) $this->title;
    }
}
