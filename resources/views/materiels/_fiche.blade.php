{{-- Fiche d'un matériel (consultation en modale, CLAUDE.md §7). --}}
<div class="modal fade" id="ficheMateriel-{{ $materiel->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-laptop me-2'></i>{{ $materiel->designation }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Produit</div>
                            <dl class="nv-dl">
                                <dt>Catégorie</dt><dd>{{ $materiel->category?->name ?? '—' }}</dd>
                                <dt>Marque / modèle</dt><dd>{{ $materiel->brandModel() ?? '—' }}</dd>
                                <dt>État</dt><dd>{{ $materiel->condition->label() }}</dd>
                                <dt>Prix</dt><dd>{{ $materiel->priceForDisplay() ?? 'Non affiché (sur demande)' }}</dd>
                                @if ($materiel->promo_price !== null)
                                    <dt>Promotion</dt><dd>{{ \App\Models\Materiel::money($materiel->promo_price) }}{{ $materiel->promo_ends_on ? ' jusqu\'au '.$materiel->promo_ends_on->format('d/m/Y') : '' }}{{ $materiel->isOnPromo() ? ' (-'.$materiel->promoPercent().' %)' : ' (terminée)' }}</dd>
                                @endif
                                <dt>Conditions</dt><dd>{{ $materiel->price_conditions ?? '—' }}</dd>
                                <dt>Disponibilité</dt><dd>{{ $materiel->availability->label() }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Caractéristiques</div>
                            <dl class="nv-dl">
                                @forelse ($materiel->specList() as [$label, $value])
                                    <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
                                @empty
                                    <dt>—</dt><dd>Aucune caractéristique saisie</dd>
                                @endforelse
                            </dl>
                        </div>
                    </div>
                </div>
                @if ($materiel->images()->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach ($materiel->images() as $media)
                            <img src="{{ $media->thumbUrl() }}" alt="{{ $media->altText() }}" class="rounded border" style="width: 110px; height: 80px; object-fit: cover" loading="lazy">
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                @if ($materiel->is_published && Route::has('site.materiels.show'))
                    <a href="{{ route('site.materiels.show', $materiel->slug) }}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class='bx bx-globe me-1'></i>Voir sur le site</a>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
