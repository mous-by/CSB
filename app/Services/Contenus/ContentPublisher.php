<?php

namespace App\Services\Contenus;

use App\Services\Audit\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Publication des contenus (cahier §7 « publier, dépublier ») : bascule depuis les listes et journalisation explicite
 * (`<module>.published` / `<module>.unpublished`) en plus de l'audit automatique de LogsActivity.
 */
class ContentPublisher
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function toggle(Model $content, string $module): Model
    {
        return DB::transaction(function () use ($content, $module) {
            $was = (bool) $content->is_published;
            $content->update(['is_published' => ! $was]);
            $this->logChange($content, $module, $was);

            return $content;
        });
    }

    /** Journalise la publication ou la dépublication si l'état a changé. */
    public function logChange(Model $content, string $module, bool $wasPublished): void
    {
        if ((bool) $content->is_published === $wasPublished) {
            return;
        }

        $action = $content->is_published ? 'published' : 'unpublished';
        $label = ($content->is_published ? 'Publication' : 'Dépublication').' — '.$content->activityNoun().' « '.$content->activityLabel().' »';
        $this->logger->log("{$module}.{$action}", $module, $label, $content);
    }
}
