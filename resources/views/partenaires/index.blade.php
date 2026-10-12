@extends('layouts.admin')

@section('title', 'Partenaires')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Partenaires</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-share-alt me-2'></i>PARTENAIRES DE DIFFUSION</h6>
            @can('partenaires.manage')
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#create-partenaire"><i class='bx bx-plus'></i> Ajouter</button>
            @endcan
        </div>
        <div class="card-body">
            <p class="text-muted small">Un partenaire partage les contenus de CSB sur ses réseaux avec son lien personnel (et son code promo). Les visites, demandes et commandes qui en viennent lui sont attribuées ; chaque vente encaissée lui rapporte une commission au taux réglé sur sa fiche.</p>
            <div class="table-responsive">
                <table class="table" id="partenaires-table">
                    <thead>
                        <tr><th>PARTENAIRE</th><th>LIEN / CODE</th><th>TAUX</th><th>VISITES (30 J)</th><th>VENTES</th><th>À PAYER</th><th width="8%">ACTIONS</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($partenaires as $partenaire)
                            @php
                                $s = $stats[$partenaire->id];
                            @endphp
                            <tr>
                                <td><div class="fw-semibold">{{ $partenaire->name }}</div><div class="small text-muted">{{ $partenaire->user?->displayName() ?? 'Sans compte' }}</div>@unless ($partenaire->is_active)<span class="badge bg-secondary-subtle">Inactif</span>@endunless</td>
                                <td class="small"><code>?ref={{ $partenaire->ref_code }}</code>@if ($partenaire->promo_code)<br><span class="badge {{ $partenaire->promoActive() ? 'bg-success-subtle' : 'bg-secondary-subtle' }}">{{ $partenaire->promo_code }} -{{ rtrim(rtrim(number_format($partenaire->promo_discount, 2, ',', ''), '0'), ',') }} %</span>@endif</td>
                                <td class="small text-nowrap">@foreach (\App\Enums\CommissionKind::cases() as $kind){{ $kind->label() }} : <strong>{{ rtrim(rtrim(number_format($partenaire->rateFor($kind), 2, ',', ''), '0'), ',') }} %</strong><br>@endforeach</td>
                                <td data-order="{{ $s['visits_period'] }}">{{ $s['visits_period'] }}</td>
                                <td data-order="{{ $s['chiffre'] }}">{{ $s['ventes'] }}<div class="small text-muted">{{ \App\Models\Materiel::money($s['chiffre']) }}</div></td>
                                <td data-order="{{ $s['a_payer'] }}" class="text-nowrap {{ $s['a_payer'] ? 'fw-semibold text-warning-emphasis' : '' }}">{{ \App\Models\Materiel::money($s['a_payer']) }}</td>
                                <td><a href="{{ route('partenaires.show', $partenaire) }}" class="btn btn-primary btn-sm" title="Voir"><i class='bx bx-show'></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('partenaires.manage')
        @include('partenaires._form', ['item' => null])
    @endcan
@endsection

@push('scripts')
    <script>
        $('#partenaires-table').DataTable({ scrollX: false, order: [[0, 'asc']] });
        @if ($errors->any() && old('_form') === 'partenaire' && ! old('partenaire_id'))
            new bootstrap.Modal(document.getElementById('create-partenaire')).show();
        @endif
    </script>
@endpush
