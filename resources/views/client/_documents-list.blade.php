@if ($documents->isEmpty())
    <p class="text-muted mb-0">Aucun document pour l'instant : vos devis, factures et contrats apparaîtront ici.</p>
@else
    <ul class="list-unstyled mb-0 client-docs">
        @foreach ($documents as $document)
            <li>
                <i class='bx {{ $document->type->icon() }}' aria-hidden="true"></i>
                <span class="flex-grow-1"><strong>{{ $document->title }}</strong><small>{{ $document->type->label() }} · {{ $document->created_at->format('d/m/Y') }} · {{ $document->sizeForDisplay() }}</small></span>
                <a href="{{ route('client.documents.download', $document) }}" class="btn btn-outline-primary btn-sm"><i class='bx bx-download' aria-hidden="true"></i> Télécharger</a>
            </li>
        @endforeach
    </ul>
@endif
