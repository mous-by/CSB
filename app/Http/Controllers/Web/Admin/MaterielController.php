<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\MaterielRequest;
use App\Models\Category;
use App\Models\Materiel;
use App\Services\Contenus\MaterielService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Catalogue de matériel (cahier §5.4, §7) : liste filtrable avec fiche en modale, saisie sur une page dédiée,
 * publication / dépublication depuis la liste, suppression en soft delete.
 */
class MaterielController extends Controller
{
    public function __construct(private readonly MaterielService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Materiel::class);

        return view('materiels.index', [
            'materiels' => Materiel::with(['category', 'mainImage', 'gallery'])->orderBy('sort_order')->orderBy('designation')->get(),
        ]);
    }

    /** Nouvelle fiche ; avec ?modele={id} (« Dupliquer »), pré-remplie d'après une fiche existante. */
    public function create(Request $request): View
    {
        Gate::authorize('create', Materiel::class);

        $source = $request->integer('modele') ? Materiel::with(['gallery', 'mainImage'])->find($request->integer('modele')) : null;
        // La copie garde le produit (désignation, caractéristiques, prix, photos, conseiller), pas ce qui est propre à
        // la fiche d'origine : adresse, référence de stock, publication, mise en avant, promotion, référencement.
        $modele = $source?->replicate(['slug', 'reference', 'stock_quantity', 'stock_synced_at', 'is_published', 'published_at', 'is_featured',
            'promo_price', 'promo_ends_on', 'seo_title', 'seo_description', 'seo_media_id'])
            ->setRelation('gallery', $source->gallery)->setRelation('mainImage', $source->mainImage);

        return view('materiels.form', $this->formData(null) + ['modele' => $modele]);
    }

    public function store(MaterielRequest $request): RedirectResponse
    {
        $materiel = $this->service->save(null, $request->validated(), $request->user(), $request->file('main_upload'), $request->file('gallery_upload', []));

        return redirect()->route('materiels.index')->with('status', "Matériel « {$materiel->designation} » créé".($materiel->is_published ? ' et publié.' : ' (non publié).'));
    }

    public function edit(Materiel $materiel): View
    {
        Gate::authorize('update', $materiel);

        return view('materiels.form', $this->formData($materiel->load(['gallery', 'mainImage', 'seoImage'])));
    }

    public function update(MaterielRequest $request, Materiel $materiel): RedirectResponse
    {
        $materiel = $this->service->save($materiel, $request->validated(), $request->user(), $request->file('main_upload'), $request->file('gallery_upload', []));

        return redirect()->route('materiels.index')->with('status', "Matériel « {$materiel->designation} » enregistré.");
    }

    public function publication(Materiel $materiel): RedirectResponse
    {
        Gate::authorize('publish', $materiel);

        $materiel = $this->service->togglePublication($materiel);

        return redirect()->route('materiels.index')->with('status', "Matériel « {$materiel->designation} » ".($materiel->is_published ? 'publié.' : 'dépublié : il n\'apparaît plus sur le site.'));
    }

    public function destroy(Materiel $materiel): RedirectResponse
    {
        Gate::authorize('delete', $materiel);

        $materiel->delete();

        return redirect()->route('materiels.index')->with('status', "Matériel « {$materiel->designation} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Materiel $materiel): array
    {
        return [
            'materiel' => $materiel,
            'categories' => Category::ofType(CategoryType::Materiel)
                ->where(fn ($q) => $q->where('is_active', true)->when($materiel, fn ($q) => $q->orWhere('id', $materiel->category_id)))
                ->ordered()->get(),
        ];
    }
}
