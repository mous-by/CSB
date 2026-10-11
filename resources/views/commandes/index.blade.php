@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Commandes</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-cart me-2'></i>COMMANDES DE LA BOUTIQUE</h6>
            @can('boutique.export')
                <a href="{{ route('commandes.export') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-spreadsheet'></i> Exporter (Excel)</a>
            @endcan
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-3"><div class="nv-panel"><div class="small text-muted">À traiter</div><div class="h4 mb-0">{{ $commandes->filter(fn ($c) => $c->status->isOpen())->count() }}</div></div></div>
                <div class="col-sm-6 col-xl-3"><div class="nv-panel"><div class="small text-muted">Chiffre du mois (payé ou livré)</div><div class="h4 mb-0">{{ \App\Models\Materiel::money($commandes->filter(fn ($c) => in_array($c->status, [\App\Enums\CommandeStatus::Payee, \App\Enums\CommandeStatus::Livree], true) && $c->created_at->isCurrentMonth())->sum('total')) }}</div></div></div>
            </div>
            <div class="nv-chips mb-3" id="commandes-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $commandes->count() }}</b></button>
                @foreach (\App\Enums\CommandeStatus::cases() as $status)
                    <button type="button" class="nv-chip" data-token="statut-{{ $status->value }}">{{ $status->label() }} <b>{{ $commandes->where('status', $status)->count() }}</b></button>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table" id="commandes-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>COMMANDE</th>
                            <th>CLIENT</th>
                            <th>TOTAL</th>
                            <th>STATUT</th>
                            <th width="8%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($commandes as $commande)
                            <tr class="{{ $commande->status === \App\Enums\CommandeStatus::Nouvelle ? 'fw-semibold' : '' }}">
                                <td data-order="{{ $commande->created_at->timestamp }}">{{ $commande->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $commande->number }}<div class="small text-muted fw-normal">{{ $commande->lignes_count }} article(s) · {{ $commande->payment_method->label() }}</div></td>
                                <td class="cell-wrap text-break">{{ $commande->name }}<div class="small text-muted fw-normal">{{ $commande->phone }}</div></td>
                                <td data-order="{{ $commande->total }}" class="text-nowrap">{{ $commande->totalForDisplay() }}</td>
                                <td>
                                    <span class="d-none">statut-{{ $commande->status->value }}</span>
                                    <span class="badge {{ $commande->status->badge() }}">{{ $commande->status->label() }}</span>
                                </td>
                                <td><a href="{{ route('commandes.show', $commande) }}" class="btn btn-primary btn-sm" title="Traiter"><i class='bx bx-show'></i></a></td>
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
        const commandesTable = $('#commandes-table').DataTable({ scrollX: false, order: [[0, 'desc']] });
        document.querySelectorAll('#commandes-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#commandes-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                commandesTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
    </script>
@endpush
