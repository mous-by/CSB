@extends('layouts.admin')

@section('title', $logiciel ? 'Modifier — '.$logiciel->name : 'Nouveau logiciel')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('logiciels.index') }}">Logiciels</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $logiciel ? $logiciel->name : 'Nouveau logiciel' }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        $v = fn (string $field, $default = null) => old($field, $logiciel?->{$field} ?? $default);
        $status = old('status', $logiciel?->status->value ?? 'disponible');
        $platforms = old('platforms', $logiciel?->platforms ?? []);
        $secondary = array_map('intval', old('secondary_categories', $logiciel?->secondaryCategories->pluck('id')->all() ?? []));
        $features = old('features', $logiciel?->featureList() ?? []);
        $canPublish = auth()->user()->can('publish', \App\Models\Logiciel::class);
        $selectedGallery = old('gallery') !== null ? \App\Models\Media::whereIn('id', old('gallery'))->get()->sortBy(fn ($m) => array_search($m->id, old('gallery'))) : ($logiciel?->gallery ?? collect());
        $selectedLogo = old('logo_media_id') ? \App\Models\Media::find(old('logo_media_id')) : $logiciel?->logo;
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger"><i class='bx bx-error-circle me-1'></i>La fiche n'a pas été enregistrée : corrigez les champs signalés en rouge.</div>
    @endif

    <form method="POST" action="{{ $logiciel ? route('logiciels.update', $logiciel) : route('logiciels.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($logiciel)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-id-card me-2'></i>IDENTIFICATION</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="name" class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ $v('name') }}" maxlength="150" required placeholder="KalanNet">
                                @include('admin._field-error', ['field' => 'name'])
                            </div>
                            <div class="col-md-5">
                                <label for="tagline" class="form-label fw-semibold">Accroche</label>
                                <input type="text" class="form-control @error('tagline') is-invalid @enderror" id="tagline" name="tagline" value="{{ $v('tagline') }}" maxlength="120" placeholder="Gestion scolaire web et mobile">
                                @include('admin._field-error', ['field' => 'tagline'])
                            </div>
                            <div class="col-md-12">
                                <label for="slug" class="form-label fw-semibold">Adresse sur le site</label>
                                <div class="input-group">
                                    <span class="input-group-text small">/logiciels/</span>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="180" placeholder="générée depuis le nom">
                                </div>
                                @include('admin._field-error', ['field' => 'slug'])
                            </div>
                            <div class="col-12">
                                <label for="summary" class="form-label fw-semibold">Résumé <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('summary') is-invalid @enderror" id="summary" name="summary" rows="2" maxlength="300" required>{{ $v('summary') }}</textarea>
                                @include('admin._field-error', ['field' => 'summary'])
                                <div class="form-text">Une ou deux phrases : affiché sur la carte du catalogue.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label fw-semibold">Catégorie principale <span class="text-danger">*</span></label>
                                <select class="form-select single-select" id="category_id" name="category_id" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) $v('category_id') === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'category_id'])
                                @if ($categories->isEmpty())
                                    <div class="text-warning small mt-1">Aucune catégorie active : créez-en une (bouton « Catégories » de la liste).</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="secondary_categories" class="form-label fw-semibold">Catégories secondaires</label>
                                <select class="form-select single-select" id="secondary_categories" name="secondary_categories[]" multiple>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(in_array($category->id, $secondary, true))>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'secondary_categories.*'])
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    @foreach (\App\Enums\LogicielStatus::cases() as $case)
                                        <option value="{{ $case->value }}" @selected($status === $case->value) @disabled($case === \App\Enums\LogicielStatus::Archive && ! $canPublish && $status !== 'archive')>{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'status'])
                                <div class="form-text">Le statut annoncé doit correspondre à l'état réel du produit (cahier §5.1). « Archivé » le retire du catalogue.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="price_text" class="form-label fw-semibold">Tarif</label>
                                <input type="text" class="form-control @error('price_text') is-invalid @enderror" id="price_text" name="price_text" value="{{ $v('price_text') }}" maxlength="150" placeholder="Ex. À partir de 150 000 FCFA / an">
                                @include('admin._field-error', ['field' => 'price_text'])
                                <div class="form-text">Facultatif : seulement un tarif validé et à jour. Vide : « Sur devis ».</div>
                            </div>
                            <div class="col-12">
                                <span class="form-label fw-semibold d-block">Plateformes réellement disponibles</span>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach (\App\Enums\Platform::cases() as $platform)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="platform-{{ $platform->value }}" name="platforms[]" value="{{ $platform->value }}" @checked(in_array($platform->value, $platforms, true))>
                                            <label class="form-check-label" for="platform-{{ $platform->value }}"><i class='bx {{ $platform->icon() }} me-1'></i>{{ $platform->label() }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @include('admin._field-error', ['field' => 'platforms.*'])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-detail me-2'></i>PRÉSENTATION</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="problem" class="form-label fw-semibold">Problème traité</label>
                            <textarea class="form-control @error('problem') is-invalid @enderror" id="problem" name="problem" rows="3" maxlength="3000">{{ $v('problem') }}</textarea>
                            @include('admin._field-error', ['field' => 'problem'])
                            <div class="form-text">Le besoin du client auquel le logiciel répond (cahier §4 « Fiche logiciel »).</div>
                        </div>
                        <div class="mb-3">
                            @include('admin._rows-editor', ['name' => 'features', 'id' => 'features', 'label' => 'Fonctionnalités disponibles', 'columns' => [['placeholder' => 'Ex. Bulletins de notes']], 'values' => is_array($features) ? $features : preg_split('/\R/', (string) $features), 'addLabel' => 'Ajouter une fonctionnalité', 'help' => 'Uniquement les fonctions réellement disponibles (cahier §5.2). Entrée = nouvelle ligne ; collez une liste pour la répartir.'])
                        </div>
                        <div>
                            <label for="description" class="form-label fw-semibold">Description détaillée</label>
                            <textarea class="form-control font-monospace small @error('description') is-invalid @enderror" id="description" name="description" rows="10" maxlength="30000">{{ $v('description') }}</textarea>
                            @include('admin._field-error', ['field' => 'description'])
                            <div class="form-text">Markdown : <code>## Titre</code>, <code>**gras**</code>, <code>- liste</code>.</div>
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
                            <p class="small text-muted mb-2"><i class='bx bx-lock-alt me-1'></i>{{ $logiciel?->is_published ? 'Publié' : 'Non publié' }} — la publication relève d'un autre compte.</p>
                        @endif
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" @checked($v('is_featured', false))>
                            <label class="form-check-label" for="is_featured">Logiciel phare (mis en avant sur l'accueil)</label>
                        </div>
                        <label for="sort_order" class="form-label fw-semibold">Ordre d'affichage</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" min="0" max="9999" value="{{ $v('sort_order', 0) }}" required>
                        @include('admin._field-error', ['field' => 'sort_order'])
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-images me-2'></i>VISUELS</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @include('admin._media-picker', ['name' => 'logo_media_id', 'id' => 'logo_media_id', 'label' => 'Logo', 'selected' => [$selectedLogo], 'upload' => 'logo_upload'])
                        </div>
                        <div class="mb-3">
                            @include('admin._media-picker', ['name' => 'gallery', 'id' => 'gallery', 'label' => 'Captures d\'écran', 'multiple' => true, 'selected' => $selectedGallery, 'upload' => 'gallery_upload'])
                        </div>
                        <label for="video_url" class="form-label fw-semibold">Lien d'une vidéo</label>
                        <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" value="{{ $v('video_url') }}" maxlength="500" placeholder="Adresse de la vidéo (YouTube…)">
                        @include('admin._field-error', ['field' => 'video_url'])
                        <div class="form-text">Ouvert dans un nouvel onglet (aucune vidéo externe n'est intégrée à la page).</div>
                    </div>
                </div>

                @include('admin._seo-fields', ['model' => $logiciel])
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('logiciels.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
            </div>
        </div>
    </form>
@endsection
