<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Materiel;
use App\Models\StockSync;
use App\Services\Synchro\StockFileReader;
use App\Services\Synchro\StockSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Synchronisation du stock : état, import d'un fichier, jetons de l'API, journal. */
class SynchroController extends Controller
{
    public function index(): View
    {
        return view('synchro.index', [
            'withReference' => Materiel::whereNotNull('reference')->count(),
            'withoutReference' => Materiel::whereNull('reference')->count(),
            'syncs' => StockSync::with(['user', 'token'])->latest('id')->limit(50)->get(),
            'tokens' => ApiToken::with('creator')->latest('id')->get(),
        ]);
    }

    public function template(StockFileReader $reader): BinaryFileResponse
    {
        return response()->download($reader->template(Materiel::whereNotNull('reference')->orderBy('reference')->get()), 'modele-stock-csb.xlsx')->deleteFileAfterSend();
    }

    public function import(Request $request, StockFileReader $reader, StockSyncService $service): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,ods,csv,txt', 'max:5120']], ['file.mimes' => 'Fichier Excel (.xlsx, .xls), OpenDocument (.ods) ou CSV.'], ['file' => 'fichier']);

        try {
            $sync = $service->apply($reader->read($request->file('file')), 'fichier', $request->user(), fileName: $request->file('file')->getClientOriginalName());
        } catch (ValidationException $e) {
            // Erreurs ligne par ligne (« items.3.quantity » → « Ligne 5 »).
            $messages = collect($e->errors())->map(fn ($msgs, $key) => preg_match('/^items\.(\d+)\./', $key, $m) ? 'Ligne '.((int) $m[1] + 2).' : '.$msgs[0] : $msgs[0])->values()->take(20)->all();

            return back()->withErrors(['file' => $messages])->withInput();
        }

        $message = "{$sync->updated_count} matériel(s) mis à jour sur {$sync->matched_count} reconnu(s).";
        if ($sync->unknown_references) {
            $message .= ' Références inconnues : '.implode(', ', array_slice($sync->unknown_references, 0, 10)).(count($sync->unknown_references) > 10 ? '…' : '').'.';
        }

        return redirect()->route('synchro.index')->with('status', $message);
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']], [], ['name' => 'nom du jeton']);
        [, $plain] = ApiToken::issue($data['name'], $request->user());

        // Montré une seule fois (seule son empreinte est enregistrée).
        return redirect()->route('synchro.index')->with('new_token', $plain);
    }

    public function revokeToken(ApiToken $token): RedirectResponse
    {
        $token->update(['revoked_at' => now()]);

        return redirect()->route('synchro.index')->with('status', "Jeton « {$token->name} » révoqué : il ne donne plus accès à l'API.");
    }
}
