<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Contenu publiable (cahier §7 « publier, dépublier ») : le site public ne lit que les contenus publiés. La date de
 * première publication est conservée (tri des nouveautés, sitemap) ; dépublier ne l'efface pas.
 *
 * @mixin Model
 */
trait Publishable
{
    public static function bootPublishable(): void
    {
        static::saving(function (Model $model) {
            if ($model->is_published && $model->published_at === null) {
                $model->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_published'), true);
    }

    /** SEO : titre et description propres au contenu, sinon tirés du contenu lui-même. */
    public function seoTitle(): string
    {
        return (string) ($this->seo_title ?: $this->getAttribute($this->slugSource()));
    }
}
