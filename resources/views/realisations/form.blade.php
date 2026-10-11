@extends('layouts.admin')

@section('title', $realisation ? 'Modifier — '.$realisation->title : 'Nouvelle réalisation')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('realisations.index') }}">Réalisations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $realisation ? $realisation->title : 'Nouvelle réalisation' }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        $v = fn (string $field, $default = null) => old($field, $realisation?->{$field} ?? $default);
        $technologies = old('technologies', $realisation?->technologyList() ?? []);
        $canPublish = auth()->user()->can('publish', \App\Models\Realisation::class);
        $selectedGallery = old('gallery') !== null ? \App\Models\Media::whereIn('id', old('gallery'))->get()->sortBy(fn ($m) => array_search($m->id, old('gallery'))) : ($realisation?->gallery ?? collect());
        $selectedCover = old('cover_media_id') ? \App\Models\Media::find(old('cover_media_id')) : $realisation?->cover;
        $textareas = [
            ['context', 'Contexte', 'Le client, son activité, la situation de départ.', 4],
            ['need', 'Besoin', 'Ce que le client attendait.', 4],
            ['solution', 'Solution apportée', 'Ce que CSB a réalisé. Markdown : ## Titre, **gras**, - liste.', 8],
        ];
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger"><i class='bx bx-error-circle me-1'></i>La réalisation n'a pas été enregistrée : corrigez les champs signalés en rouge.</div>
    @endif

    <form method="POST" action="{{ $realisation ? route('realisations.update', $realisation) : route('realisations.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($realisation)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-briefcase-alt-2 me-2'></i>PROJET</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="title" class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ $v('title') }}" maxlength="200" required>
                                @include('admin._field-error', ['field' => 'title'])
                            </div>
                            <div class="col-md-5">
                                <label for="slug" class="form-label fw-semibold">Adresse sur le site</label>
                                <div class="input-group">
                                    <span class="input-group-text small">/realisations/</span>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="180" placeholder="automatique">
                                </div>
                                @include('admin._field-error', ['field' => 'slug'])
                            </div>
                            <div class="col-md-5">
                                <label for="client_name" class="form-label fw-semibold">Client</label>
                                <input type="text" class="form-control @error('client_name') is-invalid @enderror" id="client_name" name="client_name" value="{{ $v('client_name') }}" maxlength="150">
                                @include('admin._field-error', ['field' => 'client_name'])
                                <div class="form-text">Seulement si le client accepte d'être cité.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="sector" class="form-label fw-semibold">Secteur</label>
                                <input type="text" class="form-control @error('sector') is-invalid @enderror" id="sector" name="sector" value="{{ $v('sector') }}" maxlength="100" list="sectors" placeholder="Éducation">
                                <datalist id="sectors">
                                    @foreach ($sectors as $sector)
                                        <option value="{{ $sector }}">
                                    @endforeach
                                </datalist>
                                @include('admin._field-error', ['field' => 'sector'])
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label fw-semibold">Année</label>
                                <input type="number" class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ $v('year') }}" min="1990" max="{{ now()->year + 1 }}">
                                @include('admin._field-error', ['field' => 'year'])
                            </div>
                            <div class="col-12">
                                <label for="summary" class="form-label fw-semibold">Résumé <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('summary') is-invalid @enderror" id="summary" name="summary" rows="2" maxlength="300" required>{{ $v('summary') }}</textarea>
                                @include('admin._field-error', ['field' => 'summary'])
                            </div>
                            @foreach ($textareas as [$field, $label, $help, $rows])
                                <div class="col-12">
                                    <label for="{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
                                    <textarea class="form-control @if ($field === 'solution') font-monospace small @endif @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" rows="{{ $rows }}">{{ $v($field) }}</textarea>
                                    @include('admin._field-error', ['field' => $field])
                                    <div class="form-text">{{ $help }}</div>
                                </div>
                            @endforeach
                            <div class="col-md-6">
                                @include('admin._rows-editor', ['name' => 'technologies', 'id' => 'technologies', 'label' => 'Technologies', 'columns' => [['placeholder' => 'Ex. Laravel']], 'values' => is_array($technologies) ? $technologies : preg_split('/[\n,;]+/', (string) $technologies), 'addLabel' => 'Ajouter une technologie'])
                            </div>
                            <div class="col-md-6">
                                <label for="logiciel_id" class="form-label fw-semibold">Logiciel CSB utilisé</label>
                                <select class="form-select single-select" id="logiciel_id" name="logiciel_id">
                                    <option value="">— Aucun —</option>
                                    @foreach ($logiciels as $logiciel)
                                        <option value="{{ $logiciel->id }}" @selected((int) $v('logiciel_id') === $logiciel->id)>{{ $logiciel->name }}</option>
                                    @endforeach
                                </select>
                                @include('admin._field-error', ['field' => 'logiciel_id'])
                                <div class="form-text">La fiche du projet renverra vers la fiche du logiciel.</div>
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
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" @checked($v('is_published', false))>
                                <label class="form-check-label" for="is_published">Publiée (autorisation du client obtenue)</label>
                            </div>
                        @else
                            <p class="small text-muted mb-3"><i class='bx bx-lock-alt me-1'></i>{{ $realisation?->is_published ? 'Publiée' : 'Non publiée' }} — la publication relève d'un autre compte.</p>
                        @endif
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
                            @include('admin._media-picker', ['name' => 'cover_media_id', 'id' => 'cover_media_id', 'label' => 'Visuel principal', 'selected' => [$selectedCover], 'upload' => 'cover_upload'])
                        </div>
                        @include('admin._media-picker', ['name' => 'gallery', 'id' => 'gallery', 'label' => 'Autres visuels', 'multiple' => true, 'selected' => $selectedGallery, 'upload' => 'gallery_upload'])
                    </div>
                </div>

                @include('admin._seo-fields', ['model' => $realisation])
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('realisations.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
            </div>
        </div>
    </form>
@endsection
