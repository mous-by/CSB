@if ($demandes->isEmpty())
    <p class="text-muted mb-0">Aucune demande pour l'instant.</p>
@else
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Référence</th><th scope="col">Date</th><th scope="col">Demande</th><th scope="col">Statut</th></tr></thead>
            <tbody>
                @foreach ($demandes as $demande)
                    <tr>
                        <td class="fw-semibold">{{ $demande->reference() }}</td>
                        <td>{{ $demande->created_at->format('d/m/Y') }}</td>
                        <td>{{ $demande->type->label() }}@if ($demande->subject_label)<br><small class="text-muted">{{ $demande->subject_label }}</small>@endif</td>
                        <td><span class="tag {{ $demande->status->isOpen() ? 'tag-info' : ($demande->status === \App\Enums\DemandeStatus::Gagnee ? 'tag-success' : 'tag-muted') }}">{{ $demande->status->label() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
