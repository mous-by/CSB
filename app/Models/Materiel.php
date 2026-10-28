<?php

namespace App\Models;

use App\Enums\AdvisorForm;
use App\Enums\Availability;
use App\Enums\MaterielCondition;
use App\Models\Concerns\HasGallery;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Publishable;
use Database\Factories\MaterielFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Fiche d'un matériel du catalogue (cahier §5.4) : enregistrée par App\Services\Contenus\MaterielService.
 * Prix facultatif, en FCFA, affiché seulement s'il est renseigné (D-5).
 */
#[Fillable([
    'designation', 'slug', 'reference', 'category_id', 'brand', 'model', 'summary', 'description', 'specs', 'advisor_usages', 'advisor_form', 'condition', 'price',
    'promo_price', 'promo_ends_on', 'price_conditions', 'availability', 'stock_quantity', 'main_media_id', 'is_published', 'is_featured', 'sort_order', 'seo_title',
    'seo_description', 'seo_media_id',
])]
class Materiel extends Model
{
    /** @use HasFactory<MaterielFactory> */
    use HasFactory, HasGallery, HasSlug, LogsActivity, Publishable, SoftDeletes;

    public const CURRENCY = 'FCFA';

    protected function casts(): array
    {
        return [
            'condition' => MaterielCondition::class,
            'availability' => Availability::class,
            'specs' => 'array',
            'advisor_usages' => 'array',
            'advisor_form' => AdvisorForm::class,
            'price' => 'integer',
            'promo_price' => 'integer',
            'promo_ends_on' => 'date',
            'stock_quantity' => 'integer',
            'stock_synced_at' => 'datetime',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function publicPathPrefix(): ?string
    {
        return '/materiel';
    }

    public function slugSource(): string
    {
        return 'designation';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'main_media_id');
    }

    public function seoImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'seo_media_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('designation');
    }

    /** Marque et modèle, s'ils sont connus. */
    public function brandModel(): ?string
    {
        return trim(implode(' ', array_filter([$this->brand, $this->model]))) ?: null;
    }

    /** « 250 000 FCFA », ou null sans prix (prix normal, même pendant une promotion). */
    public function priceForDisplay(): ?string
    {
        return $this->price === null ? null : self::money($this->price);
    }

    /** Promotion en cours : prix promotionnel inférieur au prix normal, date de fin non dépassée (incluse). */
    public function isOnPromo(): bool
    {
        return $this->price !== null && $this->promo_price !== null && $this->promo_price < $this->price
            && ($this->promo_ends_on === null || ! $this->promo_ends_on->isBefore(today()));
    }

    /** Prix à payer : le prix promotionnel pendant la promotion, sinon le prix normal. */
    public function currentPrice(): ?int
    {
        return $this->isOnPromo() ? $this->promo_price : $this->price;
    }

    public function promoPriceForDisplay(): ?string
    {
        return $this->isOnPromo() ? self::money($this->promo_price) : null;
    }

    /** Remise en pourcentage arrondie (« -15 % »), pendant la promotion. */
    public function promoPercent(): ?int
    {
        return $this->isOnPromo() ? max(1, (int) round(100 - $this->promo_price * 100 / max(1, $this->price))) : null;
    }

    public function scopeOnPromo(Builder $query): Builder
    {
        return $query->whereNotNull('price')->whereNotNull('promo_price')->whereColumn('promo_price', '<', 'price')
            ->where(fn ($q) => $q->whereNull('promo_ends_on')->orWhereDate('promo_ends_on', '>=', today()));
    }

    public static function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' '.self::CURRENCY;
    }

    /** @return list<array{0: string, 1: string}> */
    public function specList(): array
    {
        return array_values(array_filter(array_map(fn ($row) => is_array($row) && count($row) === 2 ? [(string) $row[0], (string) $row[1]] : null, $this->specs ?? [])));
    }

    /** Toutes les images : principale puis galerie. */
    public function images(): Collection
    {
        return collect([$this->mainImage])->merge($this->gallery)->filter()->unique('id')->values();
    }

    public function shareImage(): ?Media
    {
        return $this->seoImage ?? $this->mainImage ?? $this->gallery->first();
    }

    public function seoTitle(): string
    {
        return (string) ($this->seo_title ?: $this->designation);
    }

    public function activityNoun(): string
    {
        return 'Matériel';
    }

    public function activityLabel(): string
    {
        return (string) $this->designation;
    }
}
