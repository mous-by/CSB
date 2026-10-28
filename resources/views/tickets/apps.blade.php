@extends('layouts.admin')

@section('title', 'Applications connectées')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Tickets</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Applications connectées</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (session('new_token'))
        <div class="alert alert-warning">
            <strong><i class='bx bx-key me-1'></i>Nouveau jeton — copiez-le maintenant dans le fichier .env de l'application (CSB_SUPPORT_TOKEN), il ne sera plus jamais affiché :</strong>
            <div class="input-group mt-2">
                <input type="text" class="form-control font-monospace" id="new-token" value="{{ session('new_token') }}" readonly>
                <button class="btn btn-dark" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('new-token').value); this.textContent='Copié'">Copier</button>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-plug me-2'></i>APPLICATIONS CONNECTÉES</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tickets.apps.store') }}" class="d-flex gap-2 mb-1">
                        @csrf
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" maxlength="100" value="{{ old('name') }}" placeholder="Nom de l'application (ex. KalanNet)" required aria-label="Nom de l'application">
                        <button type="submit" class="btn btn-primary text-nowrap"><i class='bx bx-plus'></i> Connecter</button>
                    </form>
                    @error('name')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <p class="small text-muted">Le nom s'affiche sur chaque ticket ouvert depuis l'application. Un nouveau jeton portant le même nom retrouve ses tickets.</p>
                    <ul class="list-unstyled mb-0">
                        @forelse ($tokens as $token)
                            <li class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                                <div>
                                    <strong>{{ $token->name }}</strong> <code>…{{ $token->last_four }}</code>
                                    @if ($token->revoked_at)<span class="badge bg-secondary-subtle">Révoqué</span>@else<span class="badge bg-success-subtle">Actif</span>@endif
                                    <div class="small text-muted">{{ $counts[$token->name] ?? 0 }} ticket(s) · dernier appel : {{ $token->last_used_at?->format('d/m/Y H:i') ?? 'jamais' }}</div>
                                </div>
                                @unless ($token->revoked_at)
                                    <form method="POST" action="{{ route('tickets.apps.revoke', $token) }}" class="js-confirm-delete" data-title="Révoquer ce jeton ?" data-name="Le jeton de {{ $token->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Révoquer"><i class='bx bx-block'></i></button>
                                    </form>
                                @endunless
                            </li>
                        @empty
                            <li class="text-muted small">Aucune application connectée.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-code-alt me-2'></i>BRANCHER UNE APPLICATION</h6></div>
                <div class="card-body small">
                    <p>Dans le fichier <code>.env</code> de l'application :</p>
                    <pre class="bg-light p-2 rounded small mb-3" style="white-space: pre-wrap">CSB_SUPPORT_URL={{ url('/') }}
CSB_SUPPORT_TOKEN=csb_…</pre>
                    <p>L'application ouvre un ticket au nom de son utilisateur (<code>reporter.ref</code> = son identifiant dans l'application) :</p>
                    <pre class="bg-light p-2 rounded small mb-2" style="white-space: pre-wrap">POST {{ url('/api/v1/tickets') }}
Authorization: Bearer csb_…
Accept: application/json

{"subject": "Bulletins qui ne s'impriment pas",
 "category": "logiciel", "priority": "haute",
 "message": "Depuis ce matin…",
 "reporter": {"ref": "42", "name": "Awa Traoré",
   "role": "Directrice", "organization": "École Kalan",
   "email": "awa@…", "phone": "+223…"}}</pre>
                    <ul class="mb-0">
                        <li><code>GET /api/v1/tickets?reporter_ref=42</code> : ses tickets ;</li>
                        <li><code>GET /api/v1/tickets/TCK-…?reporter_ref=42</code> : le fil (sans les notes internes) ;</li>
                        <li><code>POST /api/v1/tickets/TCK-…/messages</code> (<code>reporter_ref</code>, <code>body</code>) : répondre ;</li>
                        <li><code>GET /api/v1/tickets/options</code> : types et urgences.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.confirm-delete')
@endpush
