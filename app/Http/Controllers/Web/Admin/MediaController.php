<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\MediaUploadRequest;
use App\Models\Media;
use App\Services\Medias\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

/**
 * Bibliothèque d'images (cahier §7) : liste avec vignettes, téléversement multiple, texte alternatif et dossier,
 * suppression des images inutilisées. Recherche JSON pour les sélecteurs d'images des formulaires de contenu.
 */
class MediaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Media::class);

        $medias = Media::with('uploader')->latest('id')->get();

        return view('medias.index', [
            'medias' => $medias,
            'usages' => $medias->mapWithKeys(fn (Media $media) => [$media->id => $media->usageCount()]),
            'folders' => collect(config('medias.folders'))->merge($medias->pluck('folder')->filter())->unique()->sort()->values(),
        ]);
    }

    /**
     * Recherche par nom, texte alternatif ou dossier. Format Select2 : `{"results": [{"id", "text", "thumb"}]}`.
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Media::class);

        $term = trim((string) $request->query('q', ''));

        $results = Media::query()
            ->when($term !== '', fn ($query) => $query->search($term))
            ->latest('id')->limit(30)->get()
            ->map(fn (Media $media) => ['id' => $media->id, 'text' => $media->alt ?: $media->original_name, 'thumb' => $media->thumbUrl()]);

        return response()->json(['results' => $results]);
    }

    public function store(MediaUploadRequest $request, MediaService $service): RedirectResponse
    {
        $ids = $service->storeMany($request->file('fichiers'), $request->user(), $request->validated('folder'), $request->validated('alt'));

        return redirect()->route('medias.index')->with('status', count($ids).' image(s) ajoutée(s) à la bibliothèque.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        Gate::authorize('update', $media);

        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:60'],
        ], [], ['alt' => 'texte alternatif', 'folder' => 'dossier']);

        $media->update(['alt' => trim((string) ($data['alt'] ?? '')) ?: null, 'folder' => trim((string) ($data['folder'] ?? '')) ?: null]);

        return redirect()->route('medias.index')->with('status', "Image « {$media->original_name} » modifiée.");
    }

    public function destroy(Media $media, MediaService $service): RedirectResponse
    {
        Gate::authorize('delete', $media);

        try {
            $service->delete($media);
        } catch (RuntimeException $e) {
            return redirect()->route('medias.index')->with('error', $e->getMessage());
        }

        return redirect()->route('medias.index')->with('status', "Image « {$media->original_name} » supprimée.");
    }
}
