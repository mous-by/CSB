<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Bulk\BulkDefinition;
use App\Services\Bulk\BulkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Saisie en masse d'un module (config/bulk.php) : grille « ajouter des lignes » (avec collage depuis Excel), import de
 * fichier en tout ou rien et modèle Excel. La permission est portée par la route (routes/web/bulk.php).
 */
class BulkController extends Controller
{
    public function __construct(private readonly BulkService $service) {}

    public function create(Request $request): View
    {
        $definition = $this->definition($request);

        return view('admin.bulk', [
            'definition' => $definition,
            'columns' => $definition->columns(),
            'service' => $this->service,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $definition = $this->definition($request);
        $count = $this->service->store($definition, (array) $request->input('rows', []), $request->user());

        return redirect()->route($definition->indexRoute(), $this->indexParams($definition))
            ->with('status', "{$count} {$definition->label()} enregistré(e)s en une fois.");
    }

    public function import(Request $request): RedirectResponse
    {
        $definition = $this->definition($request);
        $request->validate([
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,ods,csv,txt', 'max:5120'],
        ], ['fichier.mimes' => 'Fichier .xlsx, .xls, .ods ou .csv attendu.', 'fichier.required' => 'Choisissez un fichier.'], ['fichier' => 'fichier']);

        $file = $request->file('fichier');
        $report = $this->service->import($definition, $file->getRealPath(), $file->getClientOriginalName(), $request->user());

        if (! $report['ok']) {
            return redirect()->route($definition->module().'.bulk')->with('import_errors', $report['errors'])
                ->with('error', 'Import refusé : rien n\'a été enregistré. Corrigez le fichier (liste des erreurs ci-dessous).');
        }

        return redirect()->route($definition->indexRoute(), $this->indexParams($definition))
            ->with('status', "Import réussi : {$report['created']} {$definition->label()} créé(e)s.");
    }

    public function template(Request $request): StreamedResponse
    {
        $definition = $this->definition($request);
        $book = $this->service->template($definition);

        return response()->streamDownload(function () use ($book) {
            IOFactory::createWriter($book, 'Xlsx')->save('php://output');
        }, 'modele-'.Str::slug($definition->label()).'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function definition(Request $request): BulkDefinition
    {
        $class = config('bulk.'.$request->route()->defaults['bulk_module']) ?? abort(404);

        return app($class);
    }

    /** @return array<string, string> */
    private function indexParams(BulkDefinition $definition): array
    {
        return $definition->module() === 'categories' ? ['type' => 'logiciel'] : [];
    }
}
