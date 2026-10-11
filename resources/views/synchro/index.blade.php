@extends('layouts.admin')

@section('title', 'Synchronisation du stock')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Synchronisation du stock</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (session('new_token'))
        <div class="alert alert-warning">
            <strong><i class='bx bx-key me-1'></i>Nouveau jeton — copiez-le maintenant, il ne sera plus jamais affiché :</strong>
            <div class="input-group mt-2">
                <input type="text" class="form-control font-monospace" id="new-token" value="{{ session('new_token') }}" readonly>
                <button class="btn btn-dark" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('new-token').value); this.textContent='Copié'">Copier</button>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-white"><i class='bx bx-sync me-2'></i>ÉTAT DU STOCK SYNCHRONISÉ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6"><div class="nv-panel"><div class="small text-muted">Matériels avec une référence (synchronisables)</div><div class="h4 mb-0">{{ $withReference }}</div></div></div>
                        <div class="col-sm-6"><div class="nv-panel"><div class="small text-muted">Sans référence (ignorés)</div><div class="h4 mb-0">{{ $withoutReference }}</div></div></div>
                    </div>
                    <p class="small text-muted mb-0">La <strong>référence</strong> se saisit dans la fiche de chaque matériel ; c'est le code de l'article dans le logiciel de stock. À chaque synchronisation : quantité &gt; 0 → « Disponible », 0 → « Indisponible » (le bouton « Ajouter au panier » disparaît) ; un prix envoyé remplace le prix affiché.</p>
                </div>
            </div>

            @can('synchro.import')
                <div class="card">
                    <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="mb-0 text-white"><i class='bx bx-upload me-2'></i>IMPORTER UN FICHIER DE STOCK</h6>
                        <a href="{{ route('synchro.template') }}" class="btn btn-light btn-sm"><i class='bx bx-download'></i> Modèle Excel</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('synchro.import') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2">
                            @csrf
                            <input type="file" name="file" class="form-control w-auto flex-grow-1 @error('file') is-invalid @enderror" accept=".xlsx,.xls,.ods,.csv" required aria-label="Fichier de stock">
                            <button type="submit" class="btn btn-primary"><i class='bx bx-sync me-1'></i>Synchroniser</button>
                        </form>
                        @error('file')
                            <div class="alert alert-danger mt-2 mb-0 small">@foreach ((array) $errors->get('file') as $line)@foreach ((array) $line as $msg)<div>{{ $msg }}</div>@endforeach @endforeach</div>
                        @enderror
                        <p class="small text-muted mt-2 mb-0">Colonnes : <strong>Référence</strong>, <strong>Quantité</strong> (ou « Stock »), <strong>Prix</strong> facultatif ; titres sur la première ligne, dans n'importe quel ordre. Tout ou rien : une ligne invalide refuse l'import entier.</p>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-history me-2'></i>JOURNAL DES SYNCHRONISATIONS</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="syncs-table">
                            <thead><tr><th>DATE</th><th>SOURCE</th><th>REÇUS</th><th>RECONNUS</th><th>MODIFIÉS</th><th>INCONNUES</th></tr></thead>
                            <tbody>
                                @foreach ($syncs as $sync)
                                    <tr>
                                        <td data-order="{{ $sync->created_at->timestamp }}">{{ $sync->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $sync->source === 'api' ? 'API — '.($sync->token?->name ?? 'jeton supprimé') : 'Fichier — '.($sync->user?->displayName() ?? '—') }}@if ($sync->file_name)<div class="small text-muted text-break">{{ $sync->file_name }}</div>@endif</td>
                                        <td>{{ $sync->items_count }}</td>
                                        <td>{{ $sync->matched_count }}</td>
                                        <td>{{ $sync->updated_count }}</td>
                                        <td class="small text-break">{{ $sync->unknown_references ? implode(', ', array_slice($sync->unknown_references, 0, 8)).(count($sync->unknown_references) > 8 ? '…' : '') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-white"><i class='bx bx-key me-2'></i>JETONS DE L'API</h6>
                </div>
                <div class="card-body">
                    @can('synchro.tokens')
                        <form method="POST" action="{{ route('synchro.tokens.store') }}" class="d-flex gap-2 mb-3">
                            @csrf
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" maxlength="100" placeholder="Nom (ex. JÀGO DÁNAYA — boutique Ségou)" required aria-label="Nom du jeton">
                            <button type="submit" class="btn btn-primary text-nowrap"><i class='bx bx-plus'></i> Créer</button>
                        </form>
                    @endcan
                    <ul class="list-unstyled mb-0">
                        @forelse ($tokens as $token)
                            <li class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                                <div>
                                    <strong>{{ $token->name }}</strong> <code>…{{ $token->last_four }}</code>
                                    @if ($token->revoked_at)<span class="badge bg-secondary-subtle">Révoqué</span>@else<span class="badge bg-success-subtle">Actif</span>@endif
                                    <div class="small text-muted">Dernier appel : {{ $token->last_used_at?->format('d/m/Y H:i') ?? 'jamais' }}</div>
                                </div>
                                @if (! $token->revoked_at)
                                    @can('synchro.tokens')
                                        <form method="POST" action="{{ route('synchro.tokens.revoke', $token) }}" class="js-confirm-delete" data-title="Révoquer ce jeton ?" data-name="Le jeton {{ $token->name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Révoquer"><i class='bx bx-block'></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </li>
                        @empty
                            <li class="text-muted small">Aucun jeton. Créez-en un pour connecter le logiciel de stock.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-code-alt me-2'></i>UTILISER L'API</h6></div>
                <div class="card-body small">
                    <p>Le logiciel de stock envoie les quantités (et les prix s'il le souhaite) :</p>
                    <pre class="bg-light p-2 rounded small mb-2" style="white-space: pre-wrap">POST {{ url('/api/v1/stock') }}
Authorization: Bearer csb_…
Content-Type: application/json

{"items": [
  {"reference": "HP-840-G6", "quantity": 4, "price": 285000},
  {"reference": "A4-KM720", "quantity": 0}
]}</pre>
                    <p class="mb-1">Réponse : nombre d'éléments reçus, reconnus et modifiés, et la liste des références inconnues.</p>
                    <p class="mb-0"><code>GET {{ url('/api/v1/stock') }}</code> renvoie les références connues du site. 60 appels par minute au plus.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#syncs-table').DataTable({ scrollX: false, order: [[0, 'desc']] });
    </script>
    @include('partials.confirm-delete')
@endpush
