@extends('layouts.admin')

@section('title', 'Matériel')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Matériel</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-laptop me-2'></i>CATALOGUE DE MATÉRIEL</h6>
            <div class="d-flex flex-wrap gap-2">
                @can('viewAny', [\App\Models\Category::class, \App\Enums\CategoryType::Materiel])
                    <a href="{{ route('categories.index', 'materiel') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-category'></i> Catégories</a>
                @endcan
                @can('create', \App\Models\Materiel::class)
                    <a href="{{ route('materiels.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Saisie en lot / import</a>
                @endcan
                @can('create', \App\Models\Materiel::class)
                    <a href="{{ route('materiels.create') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-plus'></i> Ajouter</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @php
                $published = $materiels->where('is_published', true)->count();
            @endphp
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="nv-chips" id="materiels-filter">
                    <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $materiels->count() }}</b></button>
                    <button type="button" class="nv-chip" data-token="pub-oui">Publiés <b>{{ $published }}</b></button>
                    <button type="button" class="nv-chip" data-token="pub-non">Non publiés <b>{{ $materiels->count() - $published }}</b></button>
                    @foreach (\App\Enums\Availability::cases() as $availability)
                        <button type="button" class="nv-chip" data-token="dispo-{{ $availability->value }}">{{ $availability->label() }} <b>{{ $materiels->where('availability', $availability)->count() }}</b></button>
                    @endforeach
                    <button type="button" class="nv-chip" data-token="en-promo">En promotion <b>{{ $materiels->filter->isOnPromo()->count() }}</b></button>
                    <button type="button" class="nv-chip" data-token="sans-prix">Sans prix <b>{{ $materiels->whereNull('price')->count() }}</b></button>
                </div>
                <div style="min-width: 200px">
                    <select class="form-select form-select-sm" id="materiels-condition-filter" aria-label="Filtrer par état">
                        <option value="">Tous les états</option>
                        @foreach (\App\Enums\MaterielCondition::cases() as $condition)
                            <option value="{{ $condition->label() }}">{{ $condition->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="materiels-table">
                    <thead>
                        <tr>
                            <th>MATÉRIEL</th>
                            <th>CATÉGORIE</th>
                            <th>ÉTAT</th>
                            <th>PRIX</th>
                            <th>DISPONIBILITÉ</th>
                            <th width="14%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materiels as $materiel)
                            <tr>
                                <td class="cell-wrap text-break">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($materiel->images()->first())
                                            <img src="{{ $materiel->images()->first()->thumbUrl() }}" alt="" class="rounded border" style="width: 48px; height: 40px; object-fit: cover">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light" style="width: 48px; height: 40px"><i class='bx bx-laptop text-primary'></i></span>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $materiel->designation }} @if ($materiel->is_featured)<i class='bx bxs-star text-warning' title="Mis en avant"></i>@endif</div>
                                            <div class="small text-muted">{{ $materiel->brandModel() ?? '—' }} · {{ $materiel->is_published ? 'publié' : 'non publié' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $materiel->category?->name ?? '—' }}</td>
                                <td><span class="badge {{ $materiel->condition->badge() }}">{{ $materiel->condition->label() }}</span></td>
                                <td data-order="{{ $materiel->currentPrice() ?? -1 }}">
                                    @if ($materiel->isOnPromo())
                                        <del class="text-muted small">{{ $materiel->priceForDisplay() }}</del><br><span class="text-danger fw-semibold">{{ $materiel->promoPriceForDisplay() }}</span> <span class="badge bg-danger">-{{ $materiel->promoPercent() }} %</span>
                                    @else
                                        {{ $materiel->priceForDisplay() ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    <span class="d-none">dispo-{{ $materiel->availability->value }} {{ $materiel->is_published ? 'pub-oui' : 'pub-non' }}{{ $materiel->price === null ? ' sans-prix' : '' }}{{ $materiel->isOnPromo() ? ' en-promo' : '' }}</span>
                                    <span class="badge {{ $materiel->availability->badge() }}">{{ $materiel->availability->label() }}</span>
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheMateriel-{{ $materiel->id }}" title="Fiche"><i class='bx bx-show'></i></button>
                                    @can('update', $materiel)
                                        <a href="{{ route('materiels.edit', $materiel) }}" class="btn btn-primary btn-sm" title="Modifier"><i class='bx bx-edit'></i></a>
                                    @endcan
                                    @can('publish', $materiel)
                                        <form method="POST" action="{{ route('materiels.publication', $materiel) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="{{ $materiel->is_published ? 'Dépublier' : 'Publier' }}"><i class='bx {{ $materiel->is_published ? 'bx-hide' : 'bx-globe' }}'></i></button>
                                        </form>
                                    @endcan
                                    @can('delete', $materiel)
                                        <form method="POST" action="{{ route('materiels.destroy', $materiel) }}" class="js-confirm-delete" data-title="Supprimer ce matériel ?" data-name="{{ $materiel->designation }}">
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

    @foreach ($materiels as $materiel)
        @include('materiels._fiche', ['materiel' => $materiel])
    @endforeach
@endsection

@push('scripts')
    <script>
        const materielsTable = $('#materiels-table').DataTable({ scrollX: false, order: [] });
        document.querySelectorAll('#materiels-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#materiels-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                materielsTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
        $('#materiels-condition-filter').on('change', function () {
            materielsTable.column(2).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
        });
    </script>
    @include('partials.confirm-delete')
@endpush
