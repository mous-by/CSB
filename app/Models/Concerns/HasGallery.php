<?php

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Galerie ordonnée d'images (table `mediables`), modifiée par MediaService::syncGallery().
 *
 * @mixin Model
 */
trait HasGallery
{
    public function gallery(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')->withPivot('position')->orderByPivot('position');
    }
}
