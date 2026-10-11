@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Mes tickets'])
        <div class="detail-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Demandes d'assistance</h2>
                <a href="{{ route('client.tickets.create') }}" class="btn btn-accent btn-sm"><i class='bx bx-plus' aria-hidden="true"></i> Nouvelle demande</a>
            </div>
            @if ($tickets->isEmpty())
                <p class="text-muted mb-0">Aucune demande d'assistance. Un appareil en panne, un souci avec un logiciel ? Ouvrez un ticket : nous suivons votre demande jusqu'à sa résolution.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th scope="col">Numéro</th><th scope="col">Objet</th><th scope="col">Dernière activité</th><th scope="col">Statut</th></tr></thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td><a href="{{ route('client.tickets.show', $ticket) }}" class="fw-semibold">{{ $ticket->number }}</a></td>
                                    <td>{{ $ticket->subject }}<br><small class="text-muted">{{ $ticket->category->label() }}</small></td>
                                    <td>{{ $ticket->last_activity_at?->format('d/m/Y H:i') }}</td>
                                    <td><span class="tag {{ $ticket->status->tag() }}">{{ $ticket->status->label() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $tickets->links() }}</div>
            @endif
        </div>
    @endcomponent
@endsection
