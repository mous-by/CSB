@extends('layouts.admin')

@section('title', $article ? 'Modifier — '.$article->title : 'Nouvel article')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">Actualités</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $article ? $article->title : 'Nouvel article' }}</li>
                </ol>
            </nav>
        </div>
        @if ($article)
            <div class="ms-auto"><a href="{{ route('articles.preview', $article) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class='bx bx-show me-1'></i>Aperçu sur le site</a></div>
        @endif
    </div>
    <hr />

    @php
        $v = fn (string $field, $default = null) => old($field, $article?->{$field} ?? $default);
        $canPublish = auth()->user()->can('publish', \App\Models\Article::class);
        $selectedCover = old('cover_media_id') ? \App\Models\Media::find(old('cover_media_id')) : $article?->cover;
        $publishedAt = old('published_at', $article?->published_at?->format('Y-m-d\TH:i'));
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger"><i class='bx bx-error-circle me-1'></i>L'article n'a pas été enregistré : corrigez les champs signalés en rouge.</div>
    @endif

    <form method="POST" action="{{ $article ? route('articles.update', $article) : route('articles.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($article)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-pencil me-2'></i>RÉDACTION</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg @error('title') is-invalid @enderror" id="title" name="title" value="{{ $v('title') }}" maxlength="200" required>
                            @include('admin._field-error', ['field' => 'title'])
                        </div>
                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-semibold">Extrait</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror" id="excerpt" name="excerpt" rows="2" maxlength="400">{{ $v('excerpt') }}</textarea>
                            @include('admin._field-error', ['field' => 'excerpt'])
                            <div class="form-text">Affiché dans la liste des actualités. Vide : le début du texte.</div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label for="body" class="form-label fw-semibold mb-0">Texte <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm" role="tablist" aria-label="Mode d'édition">
                                    <button type="button" class="btn btn-outline-primary active" data-md-tab="write" aria-pressed="true"><i class='bx bx-pencil'></i> Écrire</button>
                                    <button type="button" class="btn btn-outline-primary" data-md-tab="preview" aria-pressed="false"><i class='bx bx-show'></i> Aperçu</button>
                                </div>
                            </div>
                            <textarea class="form-control font-monospace @error('body') is-invalid @enderror" id="body" name="body" rows="18" maxlength="100000" required data-md-url="{{ route('articles.markdown') }}">{{ $v('body') }}</textarea>
                            <div id="body-preview" class="border rounded p-3 d-none" style="min-height: 300px" aria-live="polite"></div>
                            @include('admin._field-error', ['field' => 'body'])
                            <div class="form-text d-flex flex-wrap justify-content-between gap-2">
                                <span>Markdown : <code>## Intertitre</code>, <code>**gras**</code>, <code>*italique*</code>, <code>- liste</code>, <code>[texte du lien](adresse)</code>, <code>&gt; citation</code>.</span>
                                <span id="body-stats" class="text-muted"></span>
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
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                            <select class="form-select single-select" id="category_id" name="category_id" required>
                                <option value="">— Choisir —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) $v('category_id') === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @include('admin._field-error', ['field' => 'category_id'])
                        </div>
                        @if ($canPublish)
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" @checked($v('is_published', false))>
                                <label class="form-check-label" for="is_published">Publié</label>
                            </div>
                            <label for="published_at" class="form-label fw-semibold">Date de publication</label>
                            <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror" id="published_at" name="published_at" value="{{ $publishedAt }}">
                            @include('admin._field-error', ['field' => 'published_at'])
                            <div class="form-text">Vide : maintenant. Une date future programme l'article.</div>
                        @else
                            <p class="small text-muted mb-0"><i class='bx bx-lock-alt me-1'></i>{{ $article?->isVisible() ? 'Publié' : 'Non publié' }} — la publication relève d'un autre compte.</p>
                        @endif
                        <div class="mt-3">
                            <label for="slug" class="form-label fw-semibold">Adresse</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">/actualites/</span>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ $v('slug') }}" maxlength="180" placeholder="automatique">
                            </div>
                            @include('admin._field-error', ['field' => 'slug'])
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-image me-2'></i>COUVERTURE</h6>
                    </div>
                    <div class="card-body">
                        @include('admin._media-picker', ['name' => 'cover_media_id', 'id' => 'cover_media_id', 'label' => 'Image de couverture', 'selected' => [$selectedCover], 'upload' => 'cover_upload'])
                    </div>
                </div>

                @include('admin._seo-fields', ['model' => $article])
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-end gap-2">
                <a href="{{ route('articles.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Rédaction : onglet « Aperçu » rendu par le serveur (AJAX, même rendu que le site), compteur de mots.
        (function () {
            const body = document.getElementById('body');
            const preview = document.getElementById('body-preview');
            const stats = document.getElementById('body-stats');
            const count = () => {
                const words = body.value.trim() ? body.value.trim().split(/\s+/).length : 0;
                stats.textContent = words + ' mot' + (words > 1 ? 's' : '') + ' · ' + Math.max(1, Math.ceil(words / 200)) + ' min de lecture';
            };
            body.addEventListener('input', count);
            count();

            document.querySelectorAll('[data-md-tab]').forEach((tab) => {
                tab.addEventListener('click', () => {
                    const showPreview = tab.dataset.mdTab === 'preview';
                    document.querySelectorAll('[data-md-tab]').forEach((t) => { t.classList.toggle('active', t === tab); t.setAttribute('aria-pressed', String(t === tab)); });
                    body.classList.toggle('d-none', showPreview);
                    preview.classList.toggle('d-none', !showPreview);
                    if (!showPreview) { body.focus(); return; }
                    preview.innerHTML = '<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Mise en forme…</div>';
                    $.post(body.dataset.mdUrl, { body: body.value })
                        .done((data) => { preview.innerHTML = data.html || '<p class="text-muted">Rien à afficher.</p>'; })
                        .fail(() => { preview.innerHTML = '<p class="text-danger">Aperçu indisponible : vérifiez votre connexion.</p>'; });
                });
            });
        })();
    </script>
@endpush
