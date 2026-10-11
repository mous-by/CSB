<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Ancienne adresse d'un contenu public → nouvelle adresse (redirection 301). Les chaînes sont raccourcies : une
 * redirection qui menait vers l'ancienne adresse mène directement à la nouvelle.
 */
#[Fillable(['from_path', 'to_path', 'hits', 'last_hit_at'])]
class Redirection extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    /** Enregistre « ancienne → nouvelle » et met à jour les redirections qui pointaient vers l'ancienne. */
    public static function record(string $from, string $to): void
    {
        $from = '/'.ltrim($from, '/');
        $to = '/'.ltrim($to, '/');
        if ($from === $to) {
            return;
        }

        // Une ancienne adresse redevenue active ne doit plus rediriger.
        static::where('from_path', $to)->get()->each->delete();
        static::where('to_path', $from)->get()->each(fn (self $r) => $r->update(['to_path' => $to]));
        static::updateOrCreate(['from_path' => $from], ['to_path' => $to]);
    }

    /** Redirection 301 si l'adresse demandée est une ancienne adresse, sinon null (la 404 suit son cours). */
    public static function follow(Request $request): ?RedirectResponse
    {
        try {
            $redirection = static::where('from_path', '/'.ltrim($request->path(), '/'))->first();
        } catch (Throwable) {
            return null; // base indisponible : la 404 normale s'affiche
        }

        if ($redirection === null) {
            return null;
        }

        $redirection->timestamps = false;
        $redirection->forceFill(['hits' => $redirection->hits + 1, 'last_hit_at' => now()])->saveQuietly();

        return redirect($redirection->to_path, 301);
    }

    public function activityModule(): string
    {
        return 'site';
    }

    public function activityNoun(): string
    {
        return 'Redirection';
    }

    public function activityLabel(): string
    {
        return $this->from_path.' → '.$this->to_path;
    }
}
