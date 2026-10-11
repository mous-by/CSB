{{-- Fiche d'un logiciel (consultation en modale, CLAUDE.md §7). --}}
<div class="modal fade" id="ficheLogiciel-{{ $logiciel->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-code-block me-2'></i>{{ $logiciel->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <p>{{ $logiciel->summary }}</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Catalogue</div>
                            <dl class="nv-dl">
                                <dt>Statut</dt><dd><span class="badge {{ $logiciel->status->badge() }}">{{ $logiciel->status->label() }}</span></dd>
                                <dt>Catégorie</dt><dd>{{ $logiciel->category?->name ?? '—' }}</dd>
                                <dt>Secondaires</dt><dd>{{ $logiciel->secondaryCategories->pluck('name')->implode(', ') ?: '—' }}</dd>
                                <dt>Plateformes</dt><dd>{{ collect($logiciel->platformList())->map->label()->implode(', ') ?: '—' }}</dd>
                                <dt>Tarif</dt><dd>{{ $logiciel->price_text ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Publication</div>
                            <dl class="nv-dl">
                                <dt>Site</dt><dd>{{ $logiciel->is_published ? 'Publié' : 'Non publié' }}{{ $logiciel->is_featured ? ' · logiciel phare' : '' }}</dd>
                                <dt>Publié le</dt><dd>{{ $logiciel->published_at?->format('d/m/Y') ?? '—' }}</dd>
                                <dt>Adresse</dt><dd class="text-break">/logiciels/{{ $logiciel->slug }}</dd>
                                <dt>Captures</dt><dd>{{ $logiciel->gallery->count() }}</dd>
                                <dt>Modifié le</dt><dd>{{ $logiciel->updated_at->format('d/m/Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                @if ($logiciel->featureList())
                    <div class="nv-panel mt-3">
                        <div class="nv-panel-title">Fonctionnalités</div>
                        <ul class="mb-0 small">
                            @foreach ($logiciel->featureList() as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($logiciel->gallery->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach ($logiciel->gallery as $media)
                            <img src="{{ $media->thumbUrl() }}" alt="{{ $media->altText() }}" class="rounded border" style="width: 110px; height: 70px; object-fit: cover" loading="lazy">
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                @if ($logiciel->is_published && Route::has('site.logiciels.show'))
                    <a href="{{ route('site.logiciels.show', $logiciel->slug) }}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class='bx bx-globe me-1'></i>Voir sur le site</a>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
