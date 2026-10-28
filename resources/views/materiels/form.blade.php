@extends('layouts.admin')

@section('title', $materiel ? 'Modifier — '.$materiel->designation : 'Nouveau matériel')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('materiels.index') }}">Matériel</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $materiel ? $materiel->designation : 'Nouveau matériel' }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        $v = fn (string $field, $default = null) => old($field, $materiel?->{$field} ?? $default);
        $condition = old('condition', $materiel?->condition->value ?? 'neuf');
        $availability = old('availability', $materiel?->availability->value ?? 'disponible');
        $advisorUsages = (array) (old('advisor_usages', $materiel?->advisor_usages) ?? []);
        $specs = old('specs', collect($materiel?->specList() ?? [])->map(fn ($row) => ['label' => $row[0], 'value' => $row[1]])->all());
        $canPublish = auth()->user()->can('publish', \App\Models\Materiel::class);
        $selectedGallery = old('gallery') !== null ? \App\Models\Media::whereIn('id', old('gallery'))->get()->sortBy(fn ($m) => array_search($m->id, old('gallery'))) : ($materiel?->gallery ?? collect());
        $selectedMain = old('main_media_id') ? \App\Models\Media::find(old('main_media_id')) : $materiel?->mainImage;
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger"><i class='bx bx-error-circle me-1'></i>La fiche n'a pas été enregistrée : corrigez les champs signalés en rouge.</div>
    @endif

    <form method="POST" action="{{ $materiel ? route('materiels.update', $materiel) : route('materiels.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($materiel)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-id-card me-2'></i>PRODUIT</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="designation" class="form-label fw-semibold">Désignation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ $v('designation') }}" maxlength="200" required placeholder="Ordinateur portable HP ProBook 450 G9">
                                @include('admin._field-error', ['field' => 'designation'])
                            </div>
                            <div class="col-md-5">
                                <label for="slug" class="form-label fw-semibold">Adresse sur le site</label>
                                <div class="input-group">
                                    <span class="input-group-text small">/materiel/</span>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="180" placeholder="générée automatiquement">
                                </div>
                                @include('admin._field-error', ['field' => 'slug'])
                            </div>
                            <div class="col-md-4">
                                <label for="reference" class="form-label fw-semibold">Référence (logiciel de stock)</label>
                                <input type="text" class="form-control text-uppercase @error('reference') is-invalid @enderror" id="reference" name="reference" value="{{ $v('reference') }}" maxlength="60" placeholder="HP-840-G6">
                                @include('admin._field-error', ['field' => 'reference'])
                                <div class="form-text">
                                    @if ($materiel?->stock_quantity !== null)
                                        Stock synchronisé : <strong>{{ $materiel->stock_quantity }}</strong>{{ $materiel->stock_synced_at ? ' (le '.$materiel->stock_synced_at->format('d/m/Y à H:i').')' : '' }}.
                                    @else
                                        Code de l'article dans le logiciel de stock : sert à la synchronisation.
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="category_id" class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                                <select class="form-select single-select" id="category_id" name="category_id" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) $v('category_id') === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'category_id'])
                            </div>
                            <div class="col-md-4">
                                <label for="brand" class="form-label fw-semibold">Marque</label>
                                <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ $v('brand') }}" maxlength="100" placeholder="HP">
                                @include('admin._field-error', ['field' => 'brand'])
                            </div>
                            <div class="col-md-4">
                                <label for="model" class="form-label fw-semibold">Modèle</label>
                                <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ $v('model') }}" maxlength="150" placeholder="ProBook 450 G9">
                                @include('admin._field-error', ['field' => 'model'])
                            </div>
                            <div class="col-12">
                                <label for="summary" class="form-label fw-semibold">Résumé</label>
                                <textarea class="form-control @error('summary') is-invalid @enderror" id="summary" name="summary" rows="2" maxlength="300">{{ $v('summary') }}</textarea>
                                @include('admin._field-error', ['field' => 'summary'])
                            </div>
                            <div class="col-12">
                                @include('admin._rows-editor', ['name' => 'specs', 'id' => 'specs', 'label' => 'Caractéristiques', 'columns' => [['key' => 'label', 'placeholder' => 'Libellé (ex. Mémoire)', 'width' => '40%'], ['key' => 'value', 'placeholder' => 'Valeur (ex. 8 Go)']], 'values' => is_array($specs) ? $specs : [], 'addLabel' => 'Ajouter une caractéristique', 'help' => 'Seulement des informations vérifiées (cahier §5.4). Collez plusieurs lignes « Libellé : valeur » pour les répartir.'])
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea class="form-control font-monospace small @error('description') is-invalid @enderror" id="description" name="description" rows="6" maxlength="30000">{{ $v('description') }}</textarea>
                                @include('admin._field-error', ['field' => 'description'])
                                <div class="form-text">Markdown : <code>## Titre</code>, <code>**gras**</code>, <code>- liste</code>.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-purchase-tag me-2'></i>ÉTAT, PRIX ET DISPONIBILITÉ</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="condition" class="form-label fw-semibold">État <span class="text-danger">*</span></label>
                                <select class="form-select" id="condition" name="condition" required>
                                    @foreach (\App\Enums\MaterielCondition::cases() as $case)
                                        <option value="{{ $case->value }}" @selected($condition === $case->value)>{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'condition'])
                            </div>
                            <div class="col-md-4">
                                <label for="price" class="form-label fw-semibold">Prix indicatif</label>
                                <div class="input-group">
                                    <input type="text" inputmode="numeric" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $materiel?->price !== null ? number_format($materiel->price, 0, ',', ' ') : null) }}" placeholder="250 000">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                                @include('admin._field-error', ['field' => 'price'])
                                <div class="form-text">Facultatif : vide, le site affiche « Prix sur demande ».</div>
                            </div>
                            <div class="col-md-4">
                                <label for="availability" class="form-label fw-semibold">Disponibilité <span class="text-danger">*</span></label>
                                <select class="form-select" id="availability" name="availability" required>
                                    @foreach (\App\Enums\Availability::cases() as $case)
                                        <option value="{{ $case->value }}" @selected($availability === $case->value)>{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'availability'])
                            </div>
                            <div class="col-md-4">
                                <label for="promo_price" class="form-label fw-semibold">Prix promotionnel</label>
                                <div class="input-group">
                                    <input type="text" inputmode="numeric" class="form-control @error('promo_price') is-invalid @enderror" id="promo_price" name="promo_price" value="{{ old('promo_price', $materiel?->promo_price !== null ? number_format($materiel->promo_price, 0, ',', ' ') : null) }}" placeholder="225 000">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                                @include('admin._field-error', ['field' => 'promo_price'])
                                <div class="form-text" id="promo-hint">Facultatif : le prix normal s'affiche barré à côté.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="promo_ends_on" class="form-label fw-semibold">Fin de la promotion</label>
                                <input type="date" class="form-control @error('promo_ends_on') is-invalid @enderror" id="promo_ends_on" name="promo_ends_on" value="{{ old('promo_ends_on', $materiel?->promo_ends_on?->format('Y-m-d')) }}">
                                @include('admin._field-error', ['field' => 'promo_ends_on'])
                                <div class="form-text">Vide : jusqu'à ce que vous retiriez le prix promotionnel.</div>
                            </div>
                            <div class="col-md-4"></div>
                            <div class="col-12">
                                <label for="price_conditions" class="form-label fw-semibold">Conditions du prix</label>
                                <input type="text" class="form-control @error('price_conditions') is-invalid @enderror" id="price_conditions" name="price_conditions" value="{{ $v('price_conditions') }}" maxlength="200" placeholder="Prix TTC, garantie 6 mois, hors livraison">
                                @include('admin._field-error', ['field' => 'price_conditions'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-globe me-2'></i>PUBLICATION</h6>
                    </div>
                    <div class="card-body">
                        @if ($canPublish)
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" @checked($v('is_published', false))>
                                <label class="form-check-label" for="is_published">Publié sur le site</label>
                            </div>
                        @else
                            <p class="small text-muted mb-2"><i class='bx bx-lock-alt me-1'></i>{{ $materiel?->is_published ? 'Publié' : 'Non publié' }} — la publication relève d'un autre compte.</p>
                        @endif
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" @checked($v('is_featured', false))>
                            <label class="form-check-label" for="is_featured">Mis en avant sur l'accueil</label>
                        </div>
                        <label for="sort_order" class="form-label fw-semibold">Ordre d'affichage</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" min="0" max="9999" value="{{ $v('sort_order', 0) }}" required>
                        @include('admin._field-error', ['field' => 'sort_order'])
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-compass me-2'></i>CONSEILLER D'ACHAT</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Pour un ordinateur : cochez les usages qu'il couvre bien. Le conseiller du site le proposera aux visiteurs qui en ont besoin (rien de coché = jamais proposé).</p>
                        @foreach (\App\Enums\AdvisorUsage::cases() as $usage)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="advisor_usages[]" value="{{ $usage->value }}" id="usage-{{ $usage->value }}" @checked(in_array($usage->value, $advisorUsages, true))>
                                <label class="form-check-label" for="usage-{{ $usage->value }}">{{ $usage->label() }}</label>
                            </div>
                        @endforeach
                        @include('admin._field-error', ['field' => 'advisor_usages'])
                        <label for="advisor_form" class="form-label fw-semibold mt-3">Format</label>
                        <select class="form-select @error('advisor_form') is-invalid @enderror" id="advisor_form" name="advisor_form">
                            <option value="">— Non précisé —</option>
                            @foreach (\App\Enums\AdvisorForm::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('advisor_form', $materiel?->advisor_form?->value) === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        @include('admin._field-error', ['field' => 'advisor_form'])
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-images me-2'></i>PHOTOS</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @include('admin._media-picker', ['name' => 'main_media_id', 'id' => 'main_media_id', 'label' => 'Photo principale', 'selected' => [$selectedMain], 'upload' => 'main_upload'])
                        </div>
                        @include('admin._media-picker', ['name' => 'gallery', 'id' => 'gallery', 'label' => 'Autres photos', 'multiple' => true, 'selected' => $selectedGallery, 'upload' => 'gallery_upload'])
                    </div>
                </div>

                @include('admin._seo-fields', ['model' => $materiel])
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('materiels.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Prix promotionnel : remise calculée en direct sous le champ.
        (function () {
            const num = (el) => parseInt((el.value || '').replace(/[^0-9]/g, ''), 10);
            const price = document.getElementById('price'), promo = document.getElementById('promo_price'), hint = document.getElementById('promo-hint');
            if (!price || !promo || !hint) return;
            const sync = () => {
                const p = num(price), q = num(promo);
                if (!q) { hint.textContent = "Facultatif : le prix normal s'affiche barré à côté."; hint.className = 'form-text'; return; }
                if (!p || q >= p) { hint.textContent = 'Doit être inférieur au prix normal.'; hint.className = 'form-text text-danger'; return; }
                hint.textContent = 'Remise de ' + Math.max(1, Math.round(100 - q * 100 / p)) + ' % : ' + (p - q).toLocaleString('fr-FR') + ' FCFA de moins.';
                hint.className = 'form-text text-success';
            };
            price.addEventListener('input', sync);
            promo.addEventListener('input', sync);
            sync();
        })();
    </script>
@endpush
