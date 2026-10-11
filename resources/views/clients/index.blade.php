@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clients</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-user-pin me-2'></i>CLIENTS (ESPACE CLIENT)</h6>
            @can('create', \App\Models\Client::class)
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('clients.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Saisie en lot / import</a>
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createClientModal"><i class='bx bx-plus'></i> Ajouter</button>
                </div>
            @endcan
        </div>
        <div class="card-body">
            <p class="text-muted small">Les clients créent eux-mêmes leur compte depuis le site (Espace client) ; un compte créé ici reçoit un lien pour choisir son mot de passe.</p>
            @php
                $active = $clients->where('is_active', true)->count();
            @endphp
            <div class="nv-chips mb-3" id="clients-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $clients->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-actif">Actifs <b>{{ $active }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-inactif">Désactivés <b>{{ $clients->count() - $active }}</b></button>
                <button type="button" class="nv-chip" data-token="non-confirme">E-mail non confirmé <b>{{ $clients->whereNull('email_verified_at')->count() }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="clients-table">
                    <thead><tr><th>CLIENT</th><th>CONTACT</th><th>DEMANDES</th><th>DOCUMENTS</th><th>DERNIÈRE CONNEXION</th><th>STATUT</th><th width="8%">ACTIONS</th></tr></thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td class="cell-wrap"><div class="fw-semibold">{{ $client->name }}</div><div class="small text-muted">{{ $client->organization }}</div></td>
                                <td class="small text-break">{{ $client->email }}<br>{{ $client->phone }}</td>
                                <td>{{ $client->demandes_count }}</td>
                                <td>{{ $client->documents_count }}</td>
                                <td data-order="{{ $client->last_login_at?->timestamp ?? 0 }}">{{ $client->last_login_at?->format('d/m/Y H:i') ?? 'jamais' }}</td>
                                <td>
                                    <span class="d-none">{{ $client->is_active ? 'statut-actif' : 'statut-inactif' }}{{ $client->email_verified_at ? '' : ' non-confirme' }}</span>
                                    <span class="badge {{ $client->is_active ? 'bg-success-subtle' : 'bg-secondary-subtle' }}">{{ $client->is_active ? 'Actif' : 'Désactivé' }}</span>
                                    @unless ($client->email_verified_at)<span class="badge bg-warning-subtle">E-mail à confirmer</span>@endunless
                                </td>
                                <td><a href="{{ route('clients.show', $client) }}" class="btn btn-primary btn-sm" title="Fiche"><i class='bx bx-show'></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create', \App\Models\Client::class)
        @php
            $createFailed = $errors->any() && ! old('client_id');
        @endphp
        <div class="modal fade" id="createClientModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('clients.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-user-plus me-2'></i>Nouveau client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('clients._form', ['client' => null, 'failed' => $createFailed])
                            <div class="form-check form-switch mt-3">
                                <input type="hidden" name="invite" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="invite" name="invite" value="1" checked>
                                <label class="form-check-label" for="invite">Envoyer au client un lien pour choisir son mot de passe</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @if ($createFailed)
            <script>window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createClientModal')).show());</script>
        @endif
    @endcan
@endsection

@push('scripts')
    <script>
        const clientsTable = $('#clients-table').DataTable({ scrollX: false, order: [] });
        document.querySelectorAll('#clients-filter .nv-chip').forEach((chip) => chip.addEventListener('click', () => {
            document.querySelectorAll('#clients-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
            clientsTable.column(5).search(chip.dataset.token || '', false, true).draw();
        }));
    </script>
@endpush
