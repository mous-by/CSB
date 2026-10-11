<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CommandeStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Commande;
use App\Services\Audit\ActivityLogger;
use App\Services\Boutique\CommandeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Commandes de la boutique : liste filtrable, fiche de traitement (statut, référence de paiement), export CSV audité. */
class CommandeController extends Controller
{
    public function __construct(private readonly CommandeService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Commande::class);

        return view('commandes.index', ['commandes' => Commande::withCount('lignes')->with('handler')->latest('id')->get()]);
    }

    public function show(Commande $commande): View
    {
        Gate::authorize('view', $commande);

        return view('commandes.show', [
            'commande' => $commande->load(['lignes.materiel', 'client', 'handler']),
            'instructions' => $this->service->paymentInstructions($commande),
            'history' => ActivityLog::where('subject_type', $commande->getMorphClass())->where('subject_id', $commande->id)->latest('id')->get(),
        ]);
    }

    public function status(Request $request, Commande $commande): RedirectResponse
    {
        Gate::authorize('update', $commande);

        $data = $request->validate([
            'status' => ['required', Rule::enum(CommandeStatus::class)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'cancel_reason' => ['nullable', 'required_if:status,annulee', 'string', 'max:500'],
        ], ['cancel_reason.required_if' => 'Indiquez le motif de l\'annulation.'], ['status' => 'statut', 'payment_reference' => 'référence du paiement', 'cancel_reason' => 'motif']);

        $status = CommandeStatus::from($data['status']);
        $this->service->changeStatus($commande, $status, $request->user(), $data['payment_reference'] ?? null, $data['cancel_reason'] ?? null);

        return redirect()->route('commandes.show', $commande)->with('status', "Commande {$commande->number} : {$status->label()}.");
    }

    /** Export CSV pour Excel (UTF-8 avec BOM, « ; »), une ligne par article commandé. */
    public function export(ActivityLogger $logger): StreamedResponse
    {
        Gate::authorize('viewAny', Commande::class);
        $logger->log('commandes.exported', 'commandes', 'Export CSV des commandes ('.Commande::count().')');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Commande', 'Date', 'Statut', 'Client', 'Téléphone', 'E-mail', 'Réception', 'Paiement', 'Référence paiement', 'Article', 'Quantité', 'Prix unitaire', 'Total ligne', 'Total commande'], ';');
            Commande::with('lignes')->oldest('id')->chunk(200, function ($commandes) use ($out) {
                foreach ($commandes as $c) {
                    foreach ($c->lignes as $l) {
                        fputcsv($out, [$c->number, $c->created_at->format('d/m/Y H:i'), $c->status->label(), $c->name, $c->phone, $c->email, $c->delivery_method->label(), $c->payment_method->label(), $c->payment_reference, $l->designation, $l->quantity, $l->unit_price, $l->line_total, $c->total], ';');
                    }
                }
            });
            fclose($out);
        }, 'commandes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Commande $commande): RedirectResponse
    {
        Gate::authorize('delete', $commande);
        $commande->delete();

        return redirect()->route('commandes.index')->with('status', "Commande {$commande->number} supprimée.");
    }
}
