@extends('layouts.admin')

@section('title', 'Abonnés')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Abonnés aux actualités</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-mail-send me-2'></i>ABONNÉS AUX ACTUALITÉS</h6>
            <div class="d-flex flex-wrap gap-2">
                @can('abonnes.export')
                    <a href="{{ route('abonnes.export') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-spreadsheet'></i> Exporter (Excel)</a>
                @endcan
                @can('abonnes.create')
                    <a href="{{ route('abonnes.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Ajouter en lot / import</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small">Les visiteurs s'inscrivent depuis le bas de chaque page du site ; chaque e-mail envoyé contient un lien de désinscription. Pour envoyer un article : Actualités → bouton <i class='bx bx-send'></i>.</p>
            @php
                $active = $abonnes->whereNull('unsubscribed_at')->count();
            @endphp
            <div class="nv-chips mb-3" id="abonnes-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $abonnes->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-actif">Actifs <b>{{ $active }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-desinscrit">Désinscrits <b>{{ $abonnes->count() - $active }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="abonnes-table">
                    <thead><tr><th>E-MAIL</th><th>NOM</th><th>INSCRIT LE</th><th>ORIGINE</th><th>STATUT</th><th width="10%">ACTIONS</th></tr></thead>
                    <tbody>
                        @foreach ($abonnes as $abonne)
                            <tr>
                                <td class="text-break">{{ $abonne->email }}</td>
                                <td>{{ $abonne->name ?? '—' }}</td>
                                <td data-order="{{ $abonne->subscribed_at?->timestamp }}">{{ $abonne->subscribed_at?->format('d/m/Y') }}</td>
                                <td>{{ $abonne->source === 'site' ? 'Site' : 'Administration' }}</td>
                                <td>
                                    <span class="d-none">{{ $abonne->isActive() ? 'statut-actif' : 'statut-desinscrit' }}</span>
                                    <span class="badge {{ $abonne->isActive() ? 'bg-success-subtle' : 'bg-secondary-subtle' }}">{{ $abonne->isActive() ? 'Actif' : 'Désinscrit le '.$abonne->unsubscribed_at->format('d/m/Y') }}</span>
                                </td>
                                <td class="d-flex gap-1">
                                    @can('delete', $abonne)
                                        @if ($abonne->isActive())
                                            <form method="POST" action="{{ route('abonnes.unsubscribe', $abonne) }}">@csrf<button type="submit" class="btn btn-primary btn-sm" title="Désinscrire"><i class='bx bx-bell-off'></i></button></form>
                                        @endif
                                        <form method="POST" action="{{ route('abonnes.destroy', $abonne) }}" class="js-confirm-delete" data-title="Supprimer cet abonné ?" data-name="{{ $abonne->email }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-primary btn-sm" title="Supprimer"><i class='bx bx-trash'></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const abonnesTable = $('#abonnes-table').DataTable({ scrollX: false, order: [[2, 'desc']] });
        document.querySelectorAll('#abonnes-filter .nv-chip').forEach((chip) => chip.addEventListener('click', () => {
            document.querySelectorAll('#abonnes-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
            abonnesTable.column(4).search(chip.dataset.token || '', false, true).draw();
        }));
    </script>
    @include('partials.confirm-delete')
@endpush
