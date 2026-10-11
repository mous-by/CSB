@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Mes commandes'])
        <div class="detail-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Commandes de la boutique</h2>
                <a href="{{ route('site.materiels.index') }}" class="btn btn-accent btn-sm"><i class='bx bx-cart' aria-hidden="true"></i> Boutique</a>
            </div>
            @if ($commandes->isEmpty())
                <p class="text-muted mb-0">Aucune commande pour l'instant. Les commandes passées en étant connecté apparaissent ici.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th scope="col">Numéro</th><th scope="col">Date</th><th scope="col">Total</th><th scope="col">Statut</th></tr></thead>
                        <tbody>
                            @foreach ($commandes as $commande)
                                <tr>
                                    <td><a href="{{ route('site.commande.suivi', [$commande->number, $commande->access_token]) }}" class="fw-semibold">{{ $commande->number }}</a><br><small class="text-muted">{{ $commande->lignes_count }} article(s)</small></td>
                                    <td>{{ $commande->created_at->format('d/m/Y') }}</td>
                                    <td class="text-nowrap">{{ $commande->totalForDisplay() }}</td>
                                    <td><span class="tag {{ $commande->status->tag() }}">{{ $commande->status->label() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $commandes->links() }}</div>
            @endif
        </div>
    @endcomponent
@endsection
