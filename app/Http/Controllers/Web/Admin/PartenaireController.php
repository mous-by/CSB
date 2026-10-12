<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CommissionKind;
use App\Enums\CommissionStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Demande;
use App\Models\Materiel;
use App\Models\Partenaire;
use App\Models\User;
use App\Services\Partenaires\CommissionService;
use App\Services\Partenaires\PartnerStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Partenaires de diffusion : liste, création et modification en modale (lien, code promo, taux de commission par type,
 * compte), fiche (résultats, commissions), commissions manuelles (logiciel, sur mesure), paiement par lot, annulation.
 */
class PartenaireController extends Controller
{
    public function __construct(private readonly CommissionService $commissions, private readonly PartnerStats $stats) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Partenaire::class);
        $partenaires = Partenaire::with('user')->orderByDesc('is_active')->orderBy('name')->get();

        return view('partenaires.index', [
            'partenaires' => $partenaires,
            'stats' => $partenaires->mapWithKeys(fn ($p) => [$p->id => $this->stats->for($p)]),
            'accounts' => $this->accounts(),
        ]);
    }

    public function show(Partenaire $partenaire): View
    {
        Gate::authorize('view', $partenaire);

        return view('partenaires.show', [
            'partenaire' => $partenaire->load('user'),
            'stats' => $this->stats->for($partenaire),
            'commissions' => $partenaire->commissions()->with('source')->get(),
            'demandes' => Demande::where('partenaire_id', $partenaire->id)->latest('id')->limit(20)->get(),
            'accounts' => $this->accounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Partenaire::class);
        $partenaire = Partenaire::create($this->validated($request, null));

        return redirect()->route('partenaires.show', $partenaire)->with('status', "Partenaire {$partenaire->name} créé. Son lien : ".$partenaire->link());
    }

    public function update(Request $request, Partenaire $partenaire): RedirectResponse
    {
        Gate::authorize('update', $partenaire);
        $partenaire->update($this->validated($request, $partenaire));

        return back()->with('status', "Partenaire {$partenaire->name} enregistré.");
    }

    /** Commission saisie par l'équipe : vente de logiciel, projet sur mesure, vente hors boutique (montant encaissé). */
    public function storeCommission(Request $request, Partenaire $partenaire): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(CommissionKind::class)],
            'base_amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'label' => ['required', 'string', 'max:200'],
            'demande_id' => ['nullable', 'integer', Rule::exists('demandes', 'id')->where('partenaire_id', $partenaire->id)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['kind' => 'type de vente', 'base_amount' => 'montant encaissé', 'label' => 'description']);

        $kind = CommissionKind::from($data['kind']);
        $commission = $this->commissions->record($partenaire, $kind, (int) $data['base_amount'], $data['label'],
            isset($data['demande_id']) ? Demande::find($data['demande_id']) : null, $request->user(), $data['notes'] ?? null);

        return back()->with($commission ? 'status' : 'error', $commission
            ? 'Commission enregistrée : '.Materiel::money($commission->amount)." ({$commission->rate} % de ".Materiel::money($commission->base_amount).').'
            : "Aucune commission : le taux « {$kind->label()} » de {$partenaire->name} est à 0 %. Réglez-le d'abord sur sa fiche.");
    }

    public function pay(Request $request, Partenaire $partenaire): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer'],
            'payment_reference' => ['required', 'string', 'max:100'],
        ], ['ids.required' => 'Cochez les commissions payées.'], ['payment_reference' => 'référence du paiement']);

        $total = $this->commissions->markPaid($partenaire, array_map('intval', $data['ids']), $data['payment_reference'], $request->user());

        return back()->with('status', 'Paiement enregistré : '.Materiel::money($total).'.');
    }

    public function cancelCommission(Request $request, Commission $commission): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:300']], [], ['reason' => 'motif']);
        abort_unless($commission->status === CommissionStatus::APayer, 422, 'Seule une commission à payer peut être annulée.');
        $commission->update(['status' => CommissionStatus::Annulee, 'notes' => $data['reason']]);

        return back()->with('status', 'Commission annulée.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Partenaire $partenaire): array
    {
        $request->merge([
            'ref_code' => Str::slug((string) $request->input('ref_code')) ?: null,
            'promo_code' => mb_strtoupper(trim((string) $request->input('promo_code'))) ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $rate = ['required', 'numeric', 'min:0', 'max:100'];

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'ref_code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/', Rule::unique('partenaires', 'ref_code')->ignore($partenaire)],
            'promo_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Z0-9]+$/', Rule::unique('partenaires', 'promo_code')->ignore($partenaire)],
            'promo_discount' => ['nullable', 'required_with:promo_code', 'numeric', 'min:1', 'max:90'],
            'promo_ends_on' => ['nullable', 'date'],
            'rate_materiel' => $rate, 'rate_logiciel' => $rate, 'rate_sur_mesure' => $rate,
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at'), Rule::unique('partenaires', 'user_id')->ignore($partenaire)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'payment_info' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [
            'ref_code.regex' => 'Le code du lien ne contient que des lettres minuscules, des chiffres et des tirets (ex. hamara).',
            'promo_code.regex' => 'Le code promo ne contient que des lettres et des chiffres (ex. HAMARA10).',
            'promo_discount.required_with' => 'Indiquez la remise du code promo (en %).',
            'user_id.unique' => 'Ce compte est déjà lié à un autre partenaire.',
        ], [
            'name' => 'nom', 'ref_code' => 'code du lien', 'promo_code' => 'code promo', 'promo_discount' => 'remise du code promo',
            'rate_materiel' => 'taux matériel', 'rate_logiciel' => 'taux logiciel', 'rate_sur_mesure' => 'taux sur mesure',
            'cookie_days' => 'durée d\'attribution', 'user_id' => 'compte', 'payment_info' => 'moyen de paiement',
        ]);
    }

    private function accounts()
    {
        return User::query()->whereHas('roles', fn ($q) => $q->where('name', RoleName::Communication->value))->orderBy('name')->get();
    }
}
