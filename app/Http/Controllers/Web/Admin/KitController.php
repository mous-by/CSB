<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Partenaire;
use App\Models\Realisation;
use App\Services\Partenaires\KitContent;
use App\Services\Partenaires\KitImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Kit de publication : pour chaque contenu publié, textes prêts à copier par réseau social et visuels prêts à poster.
 * Pour un partenaire connecté, son lien personnel et son code promo sont inclus automatiquement.
 */
class KitController extends Controller
{
    public function __construct(private readonly KitContent $kit, private readonly KitImageService $images) {}

    public function index(Request $request): View
    {
        return view('kit.index', [
            'partenaire' => $this->partner($request),
            'materiels' => Materiel::with(['mainImage', 'gallery'])->published()
                ->orderByRaw('CASE WHEN promo_price IS NOT NULL AND promo_price < price AND (promo_ends_on IS NULL OR promo_ends_on >= ?) THEN 0 ELSE 1 END', [today()->toDateString()])
                ->latest('published_at')->get(),
            'logiciels' => Logiciel::with(['logo', 'gallery'])->inCatalogue()->ordered()->get(),
            'realisations' => Realisation::with(['cover', 'gallery'])->published()->ordered()->get(),
            'articles' => Article::with('cover')->published()->latest('published_at')->limit(20)->get(),
        ]);
    }

    public function show(Request $request, string $type, int $id): View
    {
        $model = $this->kit->find($type, $id);
        $data = $this->kit->data($type, $model, $this->partner($request));

        return view('kit.show', ['type' => $type, 'model' => $model, 'data' => $data, 'texts' => $this->kit->texts($data), 'partenaire' => $this->partner($request)]);
    }

    public function image(Request $request, string $type, int $id, string $format): BinaryFileResponse
    {
        abort_unless(isset(KitImageService::FORMATS[$format]), 404);
        $model = $this->kit->find($type, $id);
        $path = $this->images->render($type.'-'.$id.($this->partner($request) ? '-p'.$this->partner($request)->id : ''), $format, $this->kit->data($type, $model, $this->partner($request)));

        $file = Storage::disk('public')->path($path);

        return $request->boolean('download')
            ? response()->download($file, 'csb-'.$type.'-'.$id.'-'.$format.'.png')
            : response()->file($file, ['Content-Type' => 'image/png']);
    }

    private function partner(Request $request): ?Partenaire
    {
        return Partenaire::active()->where('user_id', $request->user()->id)->first();
    }
}
