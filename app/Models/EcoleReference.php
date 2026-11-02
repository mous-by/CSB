<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * École qui utilise une application connectée (KalanNet), affichée dans la bande de l'accueil. Copie envoyée par
 * l'application (Api\EcoleReferenceController) : ni saisie ni modifiée dans CSB, d'où l'absence de journal d'audit.
 */
#[Fillable(['source_app', 'ref', 'name', 'address', 'logo_path', 'logo_hash', 'is_visible'])]
class EcoleReference extends Model
{
    public const CACHE_KEY = 'site.ecoles_references';

    private const BANDE_MAX = 80;

    private const BANDE_RECENTES = 20;

    protected $table = 'ecoles_references';

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    /**
     * Écoles de la bande de l'accueil et leur nombre : seulement celles qui ont un logo (une école sans logo apparaît dès
     * qu'elle en ajoute un). Si elles sont trop nombreuses : les plus récentes toujours, puis un tirage qui change à chaque
     * renouvellement du cache. Cache vidé dès qu'une application envoie ses écoles ; tableaux seulement (config/cache.php
     * n'autorise pas les objets en cache).
     *
     * @return array{total: int, ecoles: Collection<int, self>}
     */
    public static function pourLaBande(): array
    {
        $donnees = Cache::remember(self::CACHE_KEY, 3600, function () {
            $visibles = fn () => self::where('is_visible', true)->whereNotNull('logo_path');
            $recentes = $visibles()->latest('id')->limit(self::BANDE_RECENTES)->get();
            $autres = $visibles()->whereNotIn('id', $recentes->modelKeys())->inRandomOrder()
                ->limit(self::BANDE_MAX - $recentes->count())->get();

            return ['total' => $visibles()->count(), 'ecoles' => $recentes->concat($autres)->toArray()];
        });

        return ['total' => $donnees['total'], 'ecoles' => self::hydrate($donnees['ecoles'])];
    }
}
