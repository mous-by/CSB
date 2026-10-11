<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\RealisationRequest;
use App\Models\Logiciel;
use App\Models\Realisation;
use App\Services\Contenus\ContentPublisher;
use App\Services\Contenus\RealisationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Réalisations (cahier §4, §7) : liste, saisie sur une page dédiée, publication depuis la liste, suppression en soft
 * delete.
 */
class RealisationController extends Controller
{
    public function __construct(private readonly RealisationService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Realisation::class);

        return view('realisations.index', ['realisations' => Realisation::with(['cover', 'gallery', 'logiciel'])->ordered()->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Realisation::class);

        return view('realisations.form', $this->formData(null));
    }

    public function store(RealisationRequest $request): RedirectResponse
    {
        $realisation = $this->service->save(null, $request->validated(), $request->user(), $request->file('cover_upload'), $request->file('gallery_upload', []));

        return redirect()->route('realisations.index')->with('status', "Réalisation « {$realisation->title} » créée".($realisation->is_published ? ' et publiée.' : ' (non publiée).'));
    }

    public function edit(Realisation $realisation): View
    {
        Gate::authorize('update', $realisation);

        return view('realisations.form', $this->formData($realisation->load(['cover', 'gallery', 'seoImage'])));
    }

    public function update(RealisationRequest $request, Realisation $realisation): RedirectResponse
    {
        $realisation = $this->service->save($realisation, $request->validated(), $request->user(), $request->file('cover_upload'), $request->file('gallery_upload', []));

        return redirect()->route('realisations.index')->with('status', "Réalisation « {$realisation->title} » enregistrée.");
    }

    public function publication(Realisation $realisation, ContentPublisher $publisher): RedirectResponse
    {
        Gate::authorize('publish', $realisation);

        $publisher->toggle($realisation, 'realisations');

        return redirect()->route('realisations.index')->with('status', "Réalisation « {$realisation->title} » ".($realisation->is_published ? 'publiée.' : 'dépubliée.'));
    }

    public function destroy(Realisation $realisation): RedirectResponse
    {
        Gate::authorize('delete', $realisation);

        $realisation->delete();

        return redirect()->route('realisations.index')->with('status', "Réalisation « {$realisation->title} » supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Realisation $realisation): array
    {
        return [
            'realisation' => $realisation,
            'logiciels' => Logiciel::orderBy('name')->get(['id', 'name']),
            'sectors' => Realisation::query()->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
        ];
    }
}
