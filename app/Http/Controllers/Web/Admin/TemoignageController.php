<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\TemoignageRequest;
use App\Models\Temoignage;
use App\Services\Temoignages\TemoignageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Témoignages de clients (cahier §4) : écrits et vidéo (envoi par morceaux, conversion en arrière-plan, ou lien),
 * photo et note ; modération de ceux envoyés par les clients depuis le site. Création et modification en modale.
 */
class TemoignageController extends Controller
{
    public function __construct(private readonly TemoignageService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Temoignage::class);

        return view('temoignages.index', ['temoignages' => Temoignage::with('photo')->orderByRaw("CASE WHEN source = 'client' AND is_published = 0 THEN 0 ELSE 1 END")->ordered()->get()]);
    }

    public function store(TemoignageRequest $request): RedirectResponse
    {
        $temoignage = $this->service->save(null, $request->validated(), $request->file('photo_upload'), $request->user());

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} créé.".($temoignage->video_status === Temoignage::VIDEO_PROCESSING ? ' La vidéo est en cours de préparation (quelques minutes).' : ''));
    }

    public function update(TemoignageRequest $request, Temoignage $temoignage): RedirectResponse
    {
        $this->service->save($temoignage, $request->validated(), $request->file('photo_upload'), $request->user());

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} modifié.".($temoignage->fresh()->video_status === Temoignage::VIDEO_PROCESSING ? ' La vidéo est en cours de préparation (quelques minutes).' : ''));
    }

    public function destroy(Temoignage $temoignage): RedirectResponse
    {
        Gate::authorize('delete', $temoignage);

        $temoignage->delete();

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} supprimé.");
    }
}
