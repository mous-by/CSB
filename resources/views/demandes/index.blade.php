@extends('layouts.admin')

@section('title', 'Demandes')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Demandes</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-envelope me-2'></i>DEMANDES COMMERCIALES</h6>
            @can('demandes.export')
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exportModal"><i class='bx bx-spreadsheet'></i> Exporter (Excel)</button>
            @endcan
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="nv-chips" id="demandes-filter">
                    <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $demandes->count() }}</b></button>
                    <button type="button" class="nv-chip" data-token="ouverte">À traiter <b>{{ $demandes->filter(fn ($d) => $d->status->isOpen())->count() }}</b></button>
                    @foreach (\App\Enums\DemandeStatus::cases() as $status)
                        <button type="button" class="nv-chip" data-token="statut-{{ $status->value }}">{{ $status->label() }} <b>{{ $demandes->where('status', $status)->count() }}</b></button>
                    @endforeach
                    <button type="button" class="nv-chip" data-token="mienne">Mes demandes <b>{{ $demandes->where('assigned_to', auth()->id())->count() }}</b></button>
                </div>
                <div style="min-width: 220px">
                    <select class="form-select form-select-sm" id="demandes-type-filter" aria-label="Filtrer par type">
                        <option value="">Tous les types</option>
                        @foreach (\App\Enums\DemandeType::cases() as $type)
                            <option value="{{ $type->label() }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="demandes-table">
                    <thead>
                        <tr>
                            <th>REÇUE LE</th>
                            <th>TYPE</th>
                            <th>DEMANDEUR</th>
                            <th>CONCERNE</th>
                            <th>STATUT</th>
                            <th width="8%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demandes as $demande)
                            <tr class="{{ $demande->status === \App\Enums\DemandeStatus::Nouvelle ? 'fw-semibold' : '' }}">
                                <td data-order="{{ $demande->created_at->timestamp }}">{{ $demande->created_at->format('d/m/Y H:i') }}<div class="small text-muted fw-normal">{{ $demande->reference() }}</div></td>
                                <td><i class='{{ $demande->type->icon() }} me-1 text-primary'></i>{{ $demande->type->label() }}</td>
                                <td class="cell-wrap text-break">
                                    {{ $demande->name }}
                                    <div class="small text-muted fw-normal">{{ collect([$demande->organization, $demande->contactLine()])->filter()->implode(' · ') }}</div>
                                </td>
                                <td class="cell-wrap text-break fw-normal">{{ $demande->subject_label ?? $demande->subject ?? $demande->need_type?->label() ?? '—' }}</td>
                                <td>
                                    <span class="d-none">statut-{{ $demande->status->value }}{{ $demande->status->isOpen() ? ' ouverte' : '' }}{{ $demande->assigned_to === auth()->id() ? ' mienne' : '' }}</span>
                                    <span class="badge {{ $demande->status->badge() }}">{{ $demande->status->label() }}</span>
                                    @if ($demande->assignee)
                                        <div class="small text-muted fw-normal">{{ $demande->assignee->displayName() }}</div>
                                    @endif
                                    @if ($demande->attachment_path)
                                        <i class='bx bx-paperclip text-muted' title="Pièce jointe"></i>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <a href="{{ route('demandes.show', $demande) }}" class="btn btn-primary btn-sm" title="Traiter"><i class='bx bx-show'></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('demandes.export')
        <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="GET" action="{{ route('demandes.export') }}">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-spreadsheet me-2'></i>Exporter les demandes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="export-type" class="form-label fw-semibold">Type</label>
                                    <select class="form-select" id="export-type" name="type">
                                        <option value="">Tous</option>
                                        @foreach (\App\Enums\DemandeType::cases() as $type)
                                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="export-status" class="form-label fw-semibold">Statut</label>
                                    <select class="form-select" id="export-status" name="status">
                                        <option value="">Tous</option>
                                        @foreach (\App\Enums\DemandeStatus::cases() as $status)
                                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="export-from" class="form-label fw-semibold">Du</label>
                                    <input type="date" class="form-control" id="export-from" name="from">
                                </div>
                                <div class="col-md-6">
                                    <label for="export-to" class="form-label fw-semibold">Au</label>
                                    <input type="date" class="form-control" id="export-to" name="to">
                                </div>
                            </div>
                            <p class="small text-muted mt-3 mb-0">Fichier CSV (UTF-8, « ; ») qui s'ouvre dans Excel. Chaque export est journalisé.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-download me-1'></i>Télécharger</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        const demandesTable = $('#demandes-table').DataTable({ scrollX: false, order: [[0, 'desc']] });
        document.querySelectorAll('#demandes-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#demandes-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                demandesTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
        $('#demandes-type-filter').on('change', function () {
            demandesTable.column(1).search(this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '', true, false).draw();
        });
    </script>
@endpush
