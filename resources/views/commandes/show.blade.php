@extends('layouts.admin')

@section('title', 'Commande '.$commande->number)

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('commandes.index') }}">Commandes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $commande->number }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            @if (\App\Support\PhoneNumber::normalize($commande->phone))
                <a href="tel:{{ \App\Support\PhoneNumber::normalize($commande->phone) }}" class="btn btn-primary btn-sm"><i class='bx bx-phone me-1'></i>Appeler le client</a>
            @endif
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bx-cart me-2'></i>COMMANDE {{ $commande->number }}</h6>
                    <span class="badge {{ $commande->status->badge() }}">{{ $commande->status->label() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>ARTICLE</th><th class="text-end">PRIX</th><th class="text-end">QTÉ</th><th class="text-end">TOTAL</th></tr></thead>
                            <tbody>
                                @foreach ($commande->lignes as $ligne)
                                    <tr>
                                        <td>
                                            @if ($ligne->materiel && Route::has('materiels.edit') && auth()->user()->can('materiels.update'))
                                                <a href="{{ route('materiels.edit', $ligne->materiel) }}">{{ $ligne->designation }}</a>
                                            @else
                                                {{ $ligne->designation }}
                                            @endif
                                            @if ($ligne->normal_price)<div class="small text-muted">Prix promotionnel (au lieu de {{ \App\Models\Materiel::money($ligne->normal_price) }})</div>@endif
                                        </td>
                                        <td class="text-end text-nowrap">{{ \App\Models\Materiel::money($ligne->unit_price) }}</td>
                                        <td class="text-end">{{ $ligne->quantity }}</td>
                                        <td class="text-end text-nowrap">{{ \App\Models\Materiel::money($ligne->line_total) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold"><td colspan="3" class="text-end">TOTAL</td><td class="text-end text-nowrap">{{ $commande->totalForDisplay() }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Client</div>
                                <dl class="nv-dl">
                                    <dt>Nom</dt><dd>{{ $commande->name }}</dd>
                                    <dt>Organisation</dt><dd>{{ $commande->organization ?? '—' }}</dd>
                                    <dt>Téléphone</dt><dd>{{ $commande->phone }}</dd>
                                    <dt>E-mail</dt><dd class="text-break">{{ $commande->email ?? '—' }}</dd>
                                    <dt>Compte client</dt><dd>@if ($commande->client && Route::has('clients.show') && auth()->user()->can('clients.view'))<a href="{{ route('clients.show', $commande->client) }}">{{ $commande->client->name }}</a>@else{{ $commande->client?->name ?? 'Sans compte' }}@endif</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Réception et paiement</div>
                                <dl class="nv-dl">
                                    <dt>Réception</dt><dd>{{ $commande->delivery_method->label() }}</dd>
                                    @if ($commande->address)<dt>Adresse</dt><dd>{{ $commande->address }}{{ $commande->city ? ', '.$commande->city : '' }}</dd>@endif
                                    <dt>Paiement</dt><dd>{{ $commande->payment_method->label() }}</dd>
                                    <dt>Référence</dt><dd>{{ $commande->payment_reference ?? '—' }}</dd>
                                    <dt>Passée le</dt><dd>{{ $commande->created_at->format('d/m/Y à H:i') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    @if ($commande->notes)
                        <div class="nv-panel mt-3 text-break"><div class="nv-panel-title">Remarques du client</div>{!! nl2br(e($commande->notes)) !!}</div>
                    @endif
                    <p class="small text-muted mt-3 mb-0"><i class='bx bx-info-circle'></i> Message de paiement affiché au client : « {{ $instructions }} »</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            @can('update', $commande)
                <div class="card">
                    <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-transfer-alt me-2'></i>TRAITEMENT</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('commandes.status', $commande) }}">
                            @csrf
                            @method('PUT')
                            <label for="status" class="form-label fw-semibold">Statut</label>
                            <select class="form-select mb-2" id="status" name="status">
                                @foreach (\App\Enums\CommandeStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('status', $commande->status->value) === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                            <div id="payment-group">
                                <label for="payment_reference" class="form-label fw-semibold">Référence du paiement</label>
                                <input type="text" class="form-control mb-2 @error('payment_reference') is-invalid @enderror" id="payment_reference" name="payment_reference" maxlength="100" value="{{ old('payment_reference', $commande->payment_reference) }}" placeholder="ID de transaction Orange Money, reçu…">
                            </div>
                            <div id="cancel-group" class="d-none">
                                <label for="cancel_reason" class="form-label fw-semibold">Motif de l'annulation</label>
                                <textarea class="form-control mb-2 @error('cancel_reason') is-invalid @enderror" id="cancel_reason" name="cancel_reason" rows="2" maxlength="500">{{ old('cancel_reason', $commande->cancel_reason) }}</textarea>
                                @error('cancel_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <p class="small text-muted" id="status-meaning">{{ $commande->status->meaning() }}</p>
                            <button type="submit" class="btn btn-primary w-100"><i class='bx bx-check me-1'></i>Enregistrer</button>
                            <p class="small text-muted mt-2 mb-0">Le client est prévenu par e-mail de chaque changement de statut.</p>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-history me-2'></i>HISTORIQUE</h6></div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        @foreach ($history as $log)
                            <li class="mb-2 pb-2 border-bottom">
                                <div class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->isBySuperadmin() && ! auth()->user()->isSuperadmin() ? config('brand.system_actor') : ($log->user_name ?? 'Site public') }}</div>
                                <div>{{ $log->description }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @can('delete', $commande)
                <form method="POST" action="{{ route('commandes.destroy', $commande) }}" class="js-confirm-delete" data-title="Supprimer cette commande ?" data-name="La commande {{ $commande->number }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100"><i class='bx bx-trash me-1'></i>Supprimer (doublon, test)</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const meanings = @json(collect(\App\Enums\CommandeStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->meaning()]));
            const status = document.getElementById('status');
            if (!status) return;
            const sync = () => {
                document.getElementById('cancel-group').classList.toggle('d-none', status.value !== 'annulee');
                document.getElementById('status-meaning').textContent = meanings[status.value] || '';
            };
            status.addEventListener('change', sync);
            sync();
        })();
    </script>
    @include('partials.confirm-delete')
@endpush
