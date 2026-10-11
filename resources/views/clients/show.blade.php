@extends('layouts.admin')

@section('title', 'Client — '.$client->name)

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $client->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex gap-2">
            @can('update', $client)
                <form method="POST" action="{{ route('clients.password-link', $client) }}">@csrf<button type="submit" class="btn btn-outline-primary btn-sm"><i class='bx bx-key me-1'></i>Envoyer un lien de mot de passe</button></form>
            @endcan
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-user me-2'></i>FICHE CLIENT</h6></div>
                <div class="card-body">
                    @can('update', $client)
                        <form method="POST" action="{{ route('clients.update', $client) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            @include('clients._form', ['client' => $client, 'failed' => $errors->any() && old('client_id') == $client->id])
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked($client->is_active)>
                                <label class="form-check-label" for="is_active">Compte actif (désactivé : accès à l'espace client coupé immédiatement)</label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="small text-muted">Inscrit le {{ $client->created_at->format('d/m/Y') }} · e-mail {{ $client->email_verified_at ? 'confirmé' : 'non confirmé' }} · dernière connexion : {{ $client->last_login_at?->format('d/m/Y H:i') ?? 'jamais' }}</span>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    @else
                        <dl class="nv-dl"><dt>Nom</dt><dd>{{ $client->name }}</dd><dt>E-mail</dt><dd>{{ $client->email }}</dd><dt>Téléphone</dt><dd>{{ $client->phone ?? '—' }}</dd></dl>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-envelope me-2'></i>DEMANDES ({{ $client->demandes->count() }})</h6></div>
                <div class="card-body">
                    @forelse ($client->demandes as $demande)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span><a href="{{ route('demandes.show', $demande) }}">{{ $demande->reference() }}</a> · {{ $demande->type->label() }} · {{ $demande->created_at->format('d/m/Y') }}</span>
                            <span class="badge {{ $demande->status->badge() }}">{{ $demande->status->label() }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucune demande.</p>
                    @endforelse
                </div>
            </div>
            @foreach ($extras as $extra)
                <div class="card"><div class="card-body d-flex justify-content-between"><span><i class='bx {{ $extra['icon'] }} me-1'></i>{{ $extra['title'] }}</span><strong>{{ $extra['count'] }} ({{ $extra['open'] }} {{ $extra['label'] }})</strong></div></div>
            @endforeach
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-folder me-2'></i>DOCUMENTS DE L'ESPACE CLIENT</h6></div>
                <div class="card-body">
                    @can('clients.documents')
                        <form method="POST" action="{{ route('clients.documents.store', $client) }}" enctype="multipart/form-data" class="nv-panel mb-3">
                            @csrf
                            <div class="row g-2">
                                <div class="col-8"><input type="text" class="form-control form-control-sm @error('title') is-invalid @enderror" name="title" placeholder="Titre (ex. Devis n° 12 – réseau)" value="{{ old('title') }}" required aria-label="Titre du document"></div>
                                <div class="col-4">
                                    <select class="form-select form-select-sm" name="type" aria-label="Type">
                                        @foreach (\App\Enums\DocumentType::options() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12"><input type="file" class="form-control form-control-sm @error('file') is-invalid @enderror" name="file" required aria-label="Fichier"></div>
                                @if ($errors->has('title') || $errors->has('file'))<div class="col-12 text-danger small">{{ $errors->first('title') ?: $errors->first('file') }}</div>@endif
                                <div class="col-12"><button type="submit" class="btn btn-primary btn-sm w-100"><i class='bx bx-upload me-1'></i>Déposer dans l'espace du client</button></div>
                            </div>
                        </form>
                    @endcan
                    @forelse ($client->documents as $document)
                        <div class="d-flex align-items-center gap-2 border-bottom py-2">
                            <i class='bx {{ $document->type->icon() }} fs-4 text-primary'></i>
                            <div class="flex-grow-1 small">
                                <a href="{{ route('clients.documents.download', [$client, $document]) }}" class="fw-semibold">{{ $document->title }}</a><br>
                                <span class="text-muted">{{ $document->type->label() }} · {{ $document->created_at->format('d/m/Y') }} · {{ $document->first_downloaded_at ? 'consulté par le client le '.$document->first_downloaded_at->format('d/m/Y') : 'pas encore consulté' }}</span>
                            </div>
                            @can('clients.documents')
                                <form method="POST" action="{{ route('clients.documents.destroy', [$client, $document]) }}" class="js-confirm-delete" data-title="Retirer ce document ?" data-name="{{ $document->title }}">@csrf @method('DELETE')<button type="submit" class="btn btn-primary btn-sm"><i class='bx bx-trash'></i></button></form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun document.</p>
                    @endforelse
                </div>
            </div>
            @can('delete', $client)
                <form method="POST" action="{{ route('clients.destroy', $client) }}" class="js-confirm-delete" data-title="Supprimer ce client ?" data-name="{{ $client->name }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100"><i class='bx bx-trash me-1'></i>Supprimer le compte</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.confirm-delete')
@endpush
