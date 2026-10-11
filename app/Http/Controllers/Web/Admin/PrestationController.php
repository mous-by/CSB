<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\PrestationRequest;
use App\Models\Prestation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Services de CSB (cahier §4) : liste, création et modification en modale, suppression en soft delete.
 */
class PrestationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Prestation::class);

        return view('prestations.index', ['prestations' => Prestation::query()->ordered()->get()]);
    }

    public function store(PrestationRequest $request): RedirectResponse
    {
        $prestation = Prestation::create($request->validated());

        return redirect()->route('prestations.index')->with('status', "Service « {$prestation->title} » créé.");
    }

    public function update(PrestationRequest $request, Prestation $prestation): RedirectResponse
    {
        $prestation->update($request->validated());

        return redirect()->route('prestations.index')->with('status', "Service « {$prestation->title} » modifié.");
    }

    public function destroy(Prestation $prestation): RedirectResponse
    {
        Gate::authorize('delete', $prestation);

        $prestation->delete();

        return redirect()->route('prestations.index')->with('status', "Service « {$prestation->title} » supprimé.");
    }
}
