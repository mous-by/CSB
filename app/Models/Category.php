<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Catégorie d'une famille de contenus (CategoryType). Une catégorie utilisée ne se supprime pas : elle se désactive
 * (elle reste valable pour les contenus qui la portent, mais n'est plus proposée ni affichée en filtre).
 */
#[Fillable(['type', 'name', 'slug', 'description', 'media_id', 'sort_order', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /**
     * Où une catégorie peut être utilisée, par famille : [table, colonne].
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const USAGES = [
        'logiciel' => [['logiciels', 'category_id'], ['categorie_logiciel', 'category_id']],
        'materiel' => [['materiels', 'category_id']],
        'article' => [['articles', 'category_id']],
    ];

    protected function casts(): array
    {
        return ['type' => CategoryType::class, 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function slugSource(): string
    {
        return 'name';
    }

    protected function slugScope(Builder $query): Builder
    {
        return $query->where('type', $this->type instanceof CategoryType ? $this->type->value : $this->type);
    }

    public function materiels(): HasMany
    {
        return $this->hasMany(Materiel::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function scopeOfType(Builder $query, CategoryType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Nombre de contenus (même supprimés) qui utilisent cette catégorie. */
    public function usageCount(): int
    {
        $count = 0;
        foreach (self::USAGES[$this->type->value] ?? [] as [$table, $column]) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $count += DB::table($table)->where($column, $this->id)->count();
            }
        }

        return $count;
    }

    public function activityNoun(): string
    {
        return 'Catégorie';
    }
}
