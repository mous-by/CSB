<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Demande;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Demandes\DemandeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Demandes commerciales (cahier §6.5, §7 « Consulter, rechercher, filtrer, changer les statuts et ajouter des notes
 * internes ») : liste filtrable, fiche de traitement, pièce jointe (disque privé), export CSV pour Excel (audité).
 */
class DemandeController extends Controller
{
    public function __construct(private readonly DemandeService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Demande::class);

        return view('demandes.index', ['demandes' => Demande::with('assignee')->latest('id')->get()]);
    }

    public function show(Demande $demande): View
    {
        Gate::authorize('view', $demande);

        return view('demandes.show', [
            'demande' => $demande->load(['notes.author', 'assignee', 'subjectModel', 'partenaire']),
            'history' => ActivityLog::where('subject_type', $demande->getMorphClass())->where('subject_id', $demande->id)->latest('id')->get(),
            'assignees' => User::query()->active()->whereHas('roles', fn ($q) => $q->whereIn('name', [RoleName::Commercial->value, RoleName::Administrateur->value]))->orderBy('name')->get(),
        ]);
    }

    public function status(Request $request, Demande $demande): RedirectResponse
    {
        Gate::authorize('update', $demande);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DemandeStatus::class)],
            'closed_reason' => ['nullable', 'string', 'max:500'],
        ], [], ['status' => 'statut', 'closed_reason' => 'motif']);

        $status = DemandeStatus::from($data['status']);
        $this->service->changeStatus($demande, $status, $request->user(), $data['closed_reason'] ?? null);

        return redirect()->route('demandes.show', $demande)->with('status', "Statut : {$status->label()}.");
    }

    public function assign(Request $request, Demande $demande): RedirectResponse
    {
        Gate::authorize('update', $demande);

        $data = $request->validate(['assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')]]);
        $user = isset($data['assigned_to']) ? User::find($data['assigned_to']) : null;
        $this->service->assign($demande, $user, $request->user());

        return redirect()->route('demandes.show', $demande)->with('status', $user ? "Demande attribuée à {$user->displayName()}." : 'Attribution retirée.');
    }

    /** Ajout d'une note interne : réponse JSON (ajout sans rechargement) ou redirection. */
    public function note(Request $request, Demande $demande): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $demande);

        $data = $request->validate(['body' => ['required', 'string', 'max:3000']], [], ['body' => 'note']);
        $note = $this->service->addNote($demande, $data['body'], $request->user());

        if ($request->expectsJson()) {
            return response()->json(['html' => view('demandes._note', ['note' => $note->load('author')])->render(), 'count' => $demande->notes()->count()]);
        }

        return redirect()->route('demandes.show', $demande)->with('status', 'Note ajoutée.');
    }

    public function resend(Demande $demande): RedirectResponse
    {
        Gate::authorize('update', $demande);

        return $this->service->notify($demande)
            ? redirect()->route('demandes.show', $demande)->with('status', 'Notification envoyée.')
            : redirect()->route('demandes.show', $demande)->with('error', 'L\'envoi a encore échoué : '.$demande->fresh()->notification_error);
    }

    public function attachment(Demande $demande): StreamedResponse
    {
        Gate::authorize('view', $demande);
        abort_unless($demande->attachment_path && Storage::disk('local')->exists($demande->attachment_path), 404);

        return Storage::disk('local')->download($demande->attachment_path, $demande->attachment_name);
    }

    /** Export CSV pour Excel (UTF-8 avec BOM, séparateur « ; »), filtré comme la liste (type, statut, période). */
    public function export(Request $request, ActivityLogger $logger): StreamedResponse
    {
        Gate::authorize('viewAny', Demande::class);

        $data = $request->validate([
            'type' => ['nullable', Rule::enum(DemandeType::class)],
            'status' => ['nullable', Rule::enum(DemandeStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Demande::with('assignee')
            ->when($data['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($data['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->oldest('id');

        $count = (clone $query)->count();
        $logger->log('demandes.exported', 'demandes', "Export CSV des demandes ({$count} ligne(s))", new: array_filter($data));

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Référence', 'Reçue le', 'Type', 'Statut', 'Nom', 'Organisation', 'E-mail', 'Téléphone', 'Concerne', 'Besoin', 'Objet', 'Message', 'Budget', 'Délai', 'Attribuée à', 'Motif de clôture'], ';');
            $query->chunk(500, function ($demandes) use ($out) {
                foreach ($demandes as $d) {
                    fputcsv($out, [
                        $d->reference(), $d->created_at->format('d/m/Y H:i'), $d->type->label(), $d->status->label(), $d->name, $d->organization,
                        $d->email, $d->phone, $d->subject_label, $d->need_type?->label(), $d->subject, $d->message, $d->budget, $d->deadline,
                        $d->assignee?->displayName(), $d->closed_reason,
                    ], ';');
                }
            });
            fclose($out);
        }, 'demandes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Demande $demande): RedirectResponse
    {
        Gate::authorize('delete', $demande);

        $demande->delete();

        return redirect()->route('demandes.index')->with('status', "Demande {$demande->reference()} supprimée.");
    }
}
