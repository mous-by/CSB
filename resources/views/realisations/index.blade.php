@extends('layouts.admin')

@section('title', 'Réalisations')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Réalisations</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-briefcase-alt-2 me-2'></i>RÉALISATIONS</h6>
            @can('create', \App\Models\Realisation::class)
                    <a href="{{ route('realisations.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Saisie en lot / import</a>
                @endcan
                @can('create', \App\Models\Realisation::class)
                <a href="{{ route('realisations.create') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-plus'></i> Ajouter</a>
            @endcan
        </div>
        <div class="card-body">
            <p class="text-muted small">Ne publiez un projet qu'avec l'autorisation du client (cahier §4). L'ordre d'affichage range les projets sur le site (du plus petit au plus grand).</p>
            @php
                $published = $realisations->where('is_published', true)->count();
            @endphp
            <div class="nv-chips mb-3" id="realisations-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $realisations->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="pub-oui">Publiées <b>{{ $published }}</b></button>
                <button type="button" class="nv-chip" data-token="pub-non">Non publiées <b>{{ $realisations->count() - $published }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="realisations-table">
                    <thead>
                        <tr>
                            <th>PROJET</th>
                            <th>CLIENT</th>
                            <th>SECTEUR</th>
                            <th>ANNÉE</th>
                            <th>SITE</th>
                            <th width="14%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($realisations as $realisation)
                            <tr>
                                <td class="cell-wrap text-break">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($realisation->images()->first())
                                            <img src="{{ $realisation->images()->first()->thumbUrl() }}" alt="" class="rounded border" style="width: 56px; height: 40px; object-fit: cover">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $realisation->title }}</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($realisation->summary, 90) }}</div>
                                            {{-- Téléphone : la colonne Actions est hors de l'écran, « Publier » est repris ici. --}}
                                            @if (! $realisation->is_published)
                                                @can('publish', $realisation)
                                                    <div class="d-lg-none mt-2">@include('admin._publish-button', ['action' => route('realisations.publication', $realisation), 'published' => false, 'label' => 'Publier'])</div>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $realisation->client_name ?? '—' }}</td>
                                <td>{{ $realisation->sector ?? '—' }}</td>
                                <td>{{ $realisation->year ?? '—' }}</td>
                                <td>
                                    <span class="d-none">{{ $realisation->is_published ? 'pub-oui' : 'pub-non' }}</span>
                                    <span class="badge {{ $realisation->is_published ? 'bg-success-subtle' : 'bg-secondary-subtle' }}">{{ $realisation->is_published ? 'Publiée' : 'Non publiée' }}</span>
                                </td>
                                <td class="d-flex gap-1">
                                    @if ($realisation->is_published)
                                        <a href="{{ route('site.realisations.show', $realisation->slug) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm" title="Voir sur le site"><i class='bx bx-show'></i></a>
                                    @endif
                                    @can('update', $realisation)
                                        <a href="{{ route('realisations.edit', $realisation) }}" class="btn btn-primary btn-sm" title="Modifier"><i class='bx bx-edit'></i></a>
                                    @endcan
                                    @can('publish', $realisation)
                                        @include('admin._publish-button', ['action' => route('realisations.publication', $realisation), 'published' => $realisation->is_published, 'label' => 'Publier'])
                                    @endcan
                                    @can('delete', $realisation)
                                        <form method="POST" action="{{ route('realisations.destroy', $realisation) }}" class="js-confirm-delete" data-title="Supprimer cette réalisation ?" data-name="{{ $realisation->title }}">
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
        const realisationsTable = $('#realisations-table').DataTable({ scrollX: false, order: [] });
        document.querySelectorAll('#realisations-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#realisations-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                realisationsTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
    </script>
    @include('partials.confirm-delete')
@endpush
