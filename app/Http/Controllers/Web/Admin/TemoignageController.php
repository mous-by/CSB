<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\TemoignageRequest;
use App\Models\Temoignage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Témoignages autorisés de clients (cahier §4) : liste, création et modification en modale, suppression en soft delete.
 */
class TemoignageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Temoignage::class);

        return view('temoignages.index', ['temoignages' => Temoignage::query()->ordered()->get()]);
    }

    public function store(TemoignageRequest $request): RedirectResponse
    {
        $temoignage = Temoignage::create($request->validated());

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} créé.");
    }

    public function update(TemoignageRequest $request, Temoignage $temoignage): RedirectResponse
    {
        $temoignage->update($request->validated());

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} modifié.");
    }

    public function destroy(Temoignage $temoignage): RedirectResponse
    {
        Gate::authorize('delete', $temoignage);

        $temoignage->delete();

        return redirect()->route('temoignages.index')->with('status', "Témoignage de {$temoignage->author_name} supprimé.");
    }
}
