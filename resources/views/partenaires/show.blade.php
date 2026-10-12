@extends('layouts.admin')

@section('title', 'Partenaire '.$partenaire->name)

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partenaires.index') }}">Partenaires</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $partenaire->name }}</li>
                </ol>
            </nav>
        </div>
        @can('partenaires.manage')
            <div class="ms-auto"><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#edit-partenaire-{{ $partenaire->id }}"><i class='bx bx-edit me-1'></i>Modifier (taux, code promo…)</button></div>
        @endcan
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-share-alt me-2'></i>{{ mb_strtoupper($partenaire->name) }}</h6>
            <span class="badge {{ $partenaire->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $partenaire->is_active ? 'Actif' : 'Inactif' }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <label class="form-label small fw-semibold" for="ref-link">Son lien personnel</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="ref-link" value="{{ $partenaire->link() }}" readonly>
                        <button class="btn btn-outline-primary" type="button" data-copy="#ref-link"><i class='bx bx-copy'></i> Copier</button>
                    </div>
                </div>
                <div class="col-lg-6 small">
                    <div><strong>Taux :</strong> @foreach (\App\Enums\CommissionKind::cases() as $kind){{ $kind->label() }} {{ rtrim(rtrim(number_format($partenaire->rateFor($kind), 2, ',', ''), '0'), ',') }} %{{ $loop->last ? '' : ' · ' }}@endforeach</div>
                    <div><strong>Code promo :</strong> {{ $partenaire->promo_code ? $partenaire->promo_code.' (-'.rtrim(rtrim(number_format($partenaire->promo_discount, 2, ',', ''), '0'), ',').' %'.($partenaire->promo_ends_on ? ', jusqu\'au '.$partenaire->promo_ends_on->format('d/m/Y') : '').')'.($partenaire->promoActive() ? '' : ' — inactif') : 'aucun' }}</div>
                    <div><strong>Attribution :</strong> {{ $partenaire->cookie_days }} jours après le clic · <strong>Compte :</strong> {{ $partenaire->user?->displayName() ?? 'aucun' }}</div>
                    <div><strong>Paiement :</strong> {{ $partenaire->payment_info ?? '—' }}@if ($partenaire->phone) · {{ $partenaire->phone }}@endif</div>
                </div>
            </div>
            @if (collect(\App\Enums\CommissionKind::cases())->every(fn ($k) => $partenaire->rateFor($k) <= 0))
                <div class="alert alert-warning py-2 small"><i class='bx bx-error me-1'></i>Les taux de commission sont à 0 % : aucune commission ne sera calculée. Réglez-les avec « Modifier ».</div>
            @endif
            @include('partenaires._stats', ['stats' => $stats])
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bx-wallet me-2'></i>COMMISSIONS</h6>
                    @can('partenaires.commissions')
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#add-commission"><i class='bx bx-plus'></i> Enregistrer une vente</button>
                    @endcan
                </div>
                <div class="card-body">
                    <p class="small text-muted">Les commandes de la boutique venues de ce partenaire créent leur commission automatiquement quand elles passent « Payée ». Une vente de logiciel ou un projet sur mesure s'enregistre avec « Enregistrer une vente » (montant encaissé).</p>
                    @include('partenaires._commissions', ['commissions' => $commissions, 'payable' => auth()->user()->can('partenaires.commissions'), 'formId' => 'pay-form'])
                    @can('partenaires.commissions')
                        @if ($stats['a_payer_n'])
                            <form method="POST" action="{{ route('partenaires.pay', $partenaire) }}" id="pay-form" class="nv-panel d-flex flex-wrap align-items-end gap-2">
                                @csrf
                                <div class="flex-grow-1">
                                    <label class="form-label small fw-semibold" for="payment_reference">Payer les commissions cochées — référence du paiement</label>
                                    <input type="text" class="form-control @error('payment_reference') is-invalid @enderror" id="payment_reference" name="payment_reference" maxlength="100" placeholder="ID Orange Money, reçu…" required>
                                    @error('payment_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @error('ids')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-success"><i class='bx bx-check-double me-1'></i>Marquer payées</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-envelope me-2'></i>DEMANDES VENUES DE LUI</h6></div>
                <div class="card-body">
                    @forelse ($demandes as $demande)
                        <div class="small border-bottom py-2">
                            <a href="{{ route('demandes.show', $demande) }}">{{ $demande->reference() }}</a> · {{ $demande->name }}
                            <span class="badge {{ $demande->status->badge() }}">{{ $demande->status->label() }}</span>
                            <div class="text-muted">{{ $demande->type->label() }}{{ $demande->subject_label ? ' — '.$demande->subject_label : '' }}</div>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Aucune demande pour l'instant.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @can('partenaires.manage')
        @include('partenaires._form', ['item' => $partenaire])
    @endcan
    @can('partenaires.commissions')
        <div class="modal fade" id="add-commission" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('partenaires.commissions.store', $partenaire) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-wallet me-2'></i>Enregistrer une vente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="c-kind">Type de vente</label>
                                    <select class="form-select" id="c-kind" name="kind">
                                        @foreach (\App\Enums\CommissionKind::cases() as $kind)
                                            <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value) data-rate="{{ $partenaire->rateFor($kind) }}">{{ $kind->label() }} ({{ rtrim(rtrim(number_format($partenaire->rateFor($kind), 2, ',', ''), '0'), ',') }} %)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="c-label">Description</label>
                                    <input type="text" class="form-control @error('label') is-invalid @enderror" id="c-label" name="label" value="{{ old('label') }}" maxlength="200" required placeholder="Licence KalanNet — Lycée …">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="c-amount">Montant encaissé</label>
                                    <div class="input-group">
                                        <input type="number" min="1" class="form-control @error('base_amount') is-invalid @enderror" id="c-amount" name="base_amount" value="{{ old('base_amount') }}" required>
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                    <div class="form-text" id="c-preview"></div>
                                </div>
                                @if ($demandes->isNotEmpty())
                                    <div class="col-12">
                                        <label class="form-label fw-semibold" for="c-demande">Demande d'origine (facultatif)</label>
                                        <select class="form-select" id="c-demande" name="demande_id">
                                            <option value="">—</option>
                                            @foreach ($demandes as $demande)
                                                <option value="{{ $demande->id }}">{{ $demande->reference() }} — {{ $demande->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer la commission</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => {
            const input = document.querySelector(b.dataset.copy);
            input.select();
            (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.resolve(document.execCommand('copy'))).then(() => { b.innerHTML = "<i class='bx bx-check'></i> Copié"; });
        }));
        (function () {
            const kind = document.getElementById('c-kind'), amount = document.getElementById('c-amount'), out = document.getElementById('c-preview');
            if (!kind) return;
            const sync = () => {
                const rate = parseFloat(kind.selectedOptions[0].dataset.rate || 0), base = parseInt(amount.value || 0, 10);
                out.textContent = base ? 'Commission : ' + Math.round(base * rate / 100).toLocaleString('fr-FR') + ' FCFA (' + rate + ' %)' : '';
            };
            kind.addEventListener('change', sync); amount.addEventListener('input', sync);
        })();
        document.querySelectorAll('.js-cancel-commission').forEach((form) => form.addEventListener('submit', (e) => {
            e.preventDefault();
            Swal.fire({ title: 'Annuler cette commission ?', input: 'text', inputLabel: 'Motif', inputValidator: (v) => !v && 'Indiquez le motif.', showCancelButton: true, confirmButtonText: 'Annuler la commission', cancelButtonText: 'Retour' })
                .then((r) => { if (r.isConfirmed) { form.querySelector('input[name=reason]').value = r.value; form.submit(); } });
        }));
        @if ($errors->any() && old('_form') === 'partenaire')
            new bootstrap.Modal(document.getElementById('edit-partenaire-{{ $partenaire->id }}')).show();
        @elseif ($errors->hasAny(['kind', 'base_amount', 'label']))
            new bootstrap.Modal(document.getElementById('add-commission')).show();
        @endif
    </script>
@endpush
