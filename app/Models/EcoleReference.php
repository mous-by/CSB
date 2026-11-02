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
     * Écoles de la bande de l'accueil et nombre total. Plusieurs centaines d'écoles ne tiennent pas dans une bande : les
     * plus récentes toujours (une nouvelle école apparaît aussitôt), puis celles qui ont un logo, puis un tirage qui
     * change à chaque renouvellement du cache. Cache vidé dès qu'une application envoie ses écoles ; tableaux seulement
     * (config/cache.php n'autorise pas les objets en cache).
     *
     * @return array{total: int, ecoles: Collection<int, self>}
     */
    public static function pourLaBande(): array
    {
        $donnees = Cache::remember(self::CACHE_KEY, 3600, function () {
            $visibles = fn () => self::where('is_visible', true);
            $recentes = $visibles()->latest('id')->limit(self::BANDE_RECENTES)->get();
            $autres = $visibles()->whereNotIn('id', $recentes->modelKeys())->orderByRaw('logo_path IS NULL')->inRandomOrder()
                ->limit(self::BANDE_MAX - $recentes->count())->get();

            return ['total' => $visibles()->count(), 'ecoles' => $recentes->concat($autres)->toArray()];
        });

        return ['total' => $donnees['total'], 'ecoles' => self::hydrate($donnees['ecoles'])];
    }

    /** Initiales affichées quand l'école n'a pas de logo. */
    public function initials(): string
    {
        $mots = array_filter(preg_split('/[\s\-\']+/u', mb_strtoupper($this->name)), fn ($mot) => mb_strlen($mot) > 2);
        $mots = array_values($mots ?: [$this->name]);

        return mb_substr($mots[0], 0, 1).(isset($mots[1]) ? mb_substr($mots[1], 0, 1) : '');
    }

    /** Teinte stable de la pastille des initiales. */
    public function hue(): int
    {
        return crc32($this->name) % 360;
    }
}
