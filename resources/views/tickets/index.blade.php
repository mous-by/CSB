@extends('layouts.admin')

@section('title', 'Tickets')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tickets</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-support me-2'></i>TICKETS D'ASSISTANCE</h6>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if ($pushKey)
                    <div data-push-toggle data-key="{{ $pushKey }}" data-store="{{ route('push.store') }}" data-test="{{ route('push.test') }}" class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-light btn-sm js-push-toggle"><i class='bx bx-bell-plus me-1'></i>Activer les notifications sur cet appareil</button>
                        <button type="button" class="btn btn-outline-light btn-sm js-push-test d-none">Essai</button>
                        <span class="small text-white js-push-status" role="status"></span>
                    </div>
                @endif
                @can('tickets.apps')
                    <a href="{{ route('tickets.apps.index') }}" class="btn btn-light btn-sm"><i class='bx bx-plug me-1'></i>Applications connectées</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="nv-chips mb-3" id="tickets-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $tickets->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="a-repondre">À répondre <b>{{ $tickets->filter(fn ($t) => $t->awaiting_team && $t->status->isOpen())->count() }}</b></button>
                @foreach (\App\Enums\TicketStatus::cases() as $status)
                    <button type="button" class="nv-chip" data-token="statut-{{ $status->value }}">{{ $status->teamLabel() }} <b>{{ $tickets->where('status', $status)->count() }}</b></button>
                @endforeach
                <button type="button" class="nv-chip" data-token="mien">Mes tickets <b>{{ $tickets->where('assigned_to', auth()->id())->count() }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="tickets-table">
                    <thead>
                        <tr>
                            <th>DERNIÈRE ACTIVITÉ</th>
                            <th>TICKET</th>
                            <th>CLIENT</th>
                            <th>PRIORITÉ</th>
                            <th>STATUT</th>
                            <th width="8%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr class="{{ $ticket->awaiting_team && $ticket->status->isOpen() ? 'fw-semibold' : '' }}">
                                <td data-order="{{ $ticket->last_activity_at?->timestamp }}">{{ $ticket->last_activity_at?->format('d/m/Y H:i') }}</td>
                                <td class="cell-wrap text-break">{{ $ticket->subject }}<div class="small text-muted fw-normal">{{ $ticket->number }} · {{ $ticket->category->label() }}</div></td>
                                <td class="cell-wrap text-break">{{ $ticket->requesterName() }}<div class="small text-muted fw-normal">{{ $ticket->requesterOrganization() }}@if ($ticket->isExternal()) <span class="badge bg-info-subtle text-dark">{{ $ticket->source_app }}</span>@endif</div></td>
                                <td data-order="{{ array_search($ticket->priority, \App\Enums\TicketPriority::cases(), true) }}"><span class="badge {{ $ticket->priority->badge() }}">{{ $ticket->priority->label() }}</span></td>
                                <td>
                                    <span class="d-none">statut-{{ $ticket->status->value }}{{ $ticket->awaiting_team && $ticket->status->isOpen() ? ' a-repondre' : '' }}{{ $ticket->assigned_to === auth()->id() ? ' mien' : '' }}</span>
                                    <span class="badge {{ $ticket->status->badge() }}">{{ $ticket->status->teamLabel() }}</span>
                                    @if ($ticket->assignee)<div class="small text-muted fw-normal">{{ $ticket->assignee->displayName() }}</div>@endif
                                </td>
                                <td><a href="{{ route('tickets.show', $ticket) }}" class="btn btn-primary btn-sm" title="Traiter"><i class='bx bx-show'></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/push.js') }}"></script>
    <script>
        const ticketsTable = $('#tickets-table').DataTable({ scrollX: false, order: [[0, 'desc']] });
        document.querySelectorAll('#tickets-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#tickets-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                ticketsTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
    </script>
@endpush
