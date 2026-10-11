@extends('layouts.admin')

@section('title', 'Logiciels')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Logiciels</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-code-block me-2'></i>CATALOGUE DES LOGICIELS</h6>
            <div class="d-flex flex-wrap gap-2">
                @can('viewAny', [\App\Models\Category::class, \App\Enums\CategoryType::Logiciel])
                    <a href="{{ route('categories.index', 'logiciel') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-category'></i> Catégories</a>
                @endcan
                @can('create', \App\Models\Logiciel::class)
                    <a href="{{ route('logiciels.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Saisie en lot / import</a>
                @endcan
                @can('create', \App\Models\Logiciel::class)
                    <a href="{{ route('logiciels.create') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-plus'></i> Ajouter</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @php
                $published = $logiciels->where('is_published', true);
            @endphp
            <div class="nv-chips mb-3" id="logiciels-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $logiciels->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="pub-oui">Publiés <b>{{ $published->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="pub-non">Non publiés <b>{{ $logiciels->count() - $published->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="phare">Phares <b>{{ $logiciels->where('is_featured', true)->count() }}</b></button>
                @foreach (\App\Enums\LogicielStatus::cases() as $status)
                    <button type="button" class="nv-chip" data-token="statut-{{ $status->value }}">{{ $status->label() }} <b>{{ $logiciels->where('status', $status)->count() }}</b></button>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table" id="logiciels-table">
                    <thead>
                        <tr>
                            <th>LOGICIEL</th>
                            <th>CATÉGORIE</th>
                            <th>PLATEFORMES</th>
                            <th>STATUT</th>
                            <th>SITE</th>
                            <th width="14%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logiciels as $logiciel)
                            <tr>
                                <td class="cell-wrap text-break">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($logiciel->logo)
                                            <img src="{{ $logiciel->logo->thumbUrl() }}" alt="" class="rounded border" style="width: 40px; height: 40px; object-fit: contain">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light" style="width: 40px; height: 40px"><i class='bx bx-code-block text-primary'></i></span>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $logiciel->name }} @if ($logiciel->is_featured)<i class='bx bxs-star text-warning' title="Logiciel phare"></i>@endif</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($logiciel->summary, 90) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $logiciel->category?->name ?? '—' }}</td>
                                <td class="small">{{ collect($logiciel->platformList())->map->label()->implode(', ') ?: '—' }}</td>
                                <td><span class="d-none">statut-{{ $logiciel->status->value }}</span><span class="badge {{ $logiciel->status->badge() }}">{{ $logiciel->status->label() }}</span></td>
                                <td>
                                    <span class="d-none">{{ $logiciel->is_published ? 'pub-oui' : 'pub-non' }}{{ $logiciel->is_featured ? ' phare' : '' }}</span>
                                    @if ($logiciel->is_published)
                                        <span class="badge bg-success-subtle">Publié</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Non publié</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheLogiciel-{{ $logiciel->id }}" title="Fiche"><i class='bx bx-show'></i></button>
                                    @can('update', $logiciel)
                                        <a href="{{ route('logiciels.edit', $logiciel) }}" class="btn btn-primary btn-sm" title="Modifier"><i class='bx bx-edit'></i></a>
                                    @endcan
                                    @can('publish', $logiciel)
                                        <form method="POST" action="{{ route('logiciels.publication', $logiciel) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="{{ $logiciel->is_published ? 'Dépublier' : 'Publier' }}"><i class='bx {{ $logiciel->is_published ? 'bx-hide' : 'bx-globe' }}'></i></button>
                                        </form>
                                    @endcan
                                    @can('delete', $logiciel)
                                        <form method="POST" action="{{ route('logiciels.destroy', $logiciel) }}" class="js-confirm-delete" data-title="Supprimer ce logiciel ?" data-name="{{ $logiciel->name }}">
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

    @foreach ($logiciels as $logiciel)
        @include('logiciels._fiche', ['logiciel' => $logiciel])
    @endforeach
@endsection

@push('scripts')
    <script>
        const logicielsTable = $('#logiciels-table').DataTable({ scrollX: false, order: [] });
        document.querySelectorAll('#logiciels-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#logiciels-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                const token = chip.dataset.token || '';
                logicielsTable.column(3).search(token.startsWith('statut-') ? token : '', false, true);
                logicielsTable.column(4).search(token.startsWith('statut-') ? '' : token, false, true).draw();
            });
        });
    </script>
    @include('partials.confirm-delete')
@endpush
