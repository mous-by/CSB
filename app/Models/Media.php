<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Image de la bibliothèque (disque `public`, nom généré). Créée uniquement par App\Services\Medias\MediaService, qui
 * vérifie le type réel, redimensionne et produit la vignette. Une image utilisée par un contenu ne se supprime pas.
 */
#[Fillable(['path', 'thumb_path', 'original_name', 'mime', 'size', 'width', 'height', 'alt', 'folder', 'uploaded_by'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'medias';

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn ($q) => $q->where('original_name', 'like', "%{$term}%")->orWhere('alt', 'like', "%{$term}%")->orWhere('folder', 'like', "%{$term}%"));
    }

    /** URL publique (relative à l'hôte courant : indépendante d'APP_URL). */
    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function thumbUrl(): string
    {
        return asset('storage/'.$this->thumb_path);
    }

    /** Texte alternatif (cahier §8 accessibilité, §11.1) : saisi, sinon dérivé du nom du fichier. */
    public function altText(): string
    {
        return $this->alt ?: (string) str(pathinfo($this->original_name, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->squish()->ucfirst();
    }

    public function sizeForDisplay(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    /** Nombre d'utilisations par les contenus (galeries et colonnes de REFERENCES). */
    public function usageCount(): int
    {
        $count = DB::table('mediables')->where('media_id', $this->id)->count();

        foreach (self::references() as [$table, $column]) {
            $count += DB::table($table)->where($column, $this->id)->count();
        }

        return $count;
    }

    /**
     * Colonnes des contenus qui désignent une image (logo, couverture, image de partage) : [table, colonne]
     * (config/medias.php, chaque module y ajoute les siennes) ; les galeries passent par la table `mediables`.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function references(): array
    {
        return config('medias.references', []);
    }

    public function activityNoun(): string
    {
        return 'Image';
    }

    public function activityLabel(): string
    {
        return (string) $this->original_name;
    }
}
