<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\LogicielRequest;
use App\Models\Category;
use App\Models\Logiciel;
use App\Services\Contenus\LogicielService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Catalogue des logiciels (cahier §5, §7) : liste filtrable avec fiche en modale, saisie sur une page dédiée (fiche
 * longue), publication / dépublication depuis la liste, suppression en soft delete.
 */
class LogicielController extends Controller
{
    public function __construct(private readonly LogicielService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Logiciel::class);

        return view('logiciels.index', [
            'logiciels' => Logiciel::with(['category', 'secondaryCategories', 'logo', 'gallery'])->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Logiciel::class);

        return view('logiciels.form', $this->formData(null));
    }

    public function store(LogicielRequest $request): RedirectResponse
    {
        $logiciel = $this->service->save(null, $request->validated(), $request->user(), $request->file('logo_upload'), $request->file('gallery_upload', []));

        return redirect()->route('logiciels.index')->with('status', "Logiciel « {$logiciel->name} » créé".($logiciel->is_published ? ' et publié.' : ' (non publié).'));
    }

    public function edit(Logiciel $logiciel): View
    {
        Gate::authorize('update', $logiciel);

        return view('logiciels.form', $this->formData($logiciel->load(['secondaryCategories', 'gallery', 'logo', 'seoImage'])));
    }

    public function update(LogicielRequest $request, Logiciel $logiciel): RedirectResponse
    {
        $logiciel = $this->service->save($logiciel, $request->validated(), $request->user(), $request->file('logo_upload'), $request->file('gallery_upload', []));

        return redirect()->route('logiciels.index')->with('status', "Logiciel « {$logiciel->name} » enregistré.");
    }

    public function publication(Logiciel $logiciel): RedirectResponse
    {
        Gate::authorize('publish', $logiciel);

        $logiciel = $this->service->togglePublication($logiciel);

        return redirect()->route('logiciels.index')->with('status', "Logiciel « {$logiciel->name} » ".($logiciel->is_published ? 'publié.' : 'dépublié : il n\'apparaît plus sur le site.'));
    }

    public function destroy(Logiciel $logiciel): RedirectResponse
    {
        Gate::authorize('delete', $logiciel);

        $logiciel->delete();

        return redirect()->route('logiciels.index')->with('status', "Logiciel « {$logiciel->name} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Logiciel $logiciel): array
    {
        return [
            'logiciel' => $logiciel,
            'categories' => Category::ofType(CategoryType::Logiciel)
                ->where(fn ($q) => $q->where('is_active', true)->when($logiciel, fn ($q) => $q->orWhere('id', $logiciel->category_id)->orWhereIn('id', $logiciel->secondaryCategories->pluck('id'))))
                ->ordered()->get(),
        ];
    }
}
