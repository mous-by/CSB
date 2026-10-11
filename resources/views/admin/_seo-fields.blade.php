{{-- Référencement d'un contenu (cahier §5.2 « Titre, méta-description, image de partage et URL canonique ») ; l'URL
     canonique est l'adresse du contenu. $model : contenu (ou null), $folder : dossier proposé pour l'image. --}}
<div class="card">
    <div class="card-header card-header-brand">
        <h6 class="mb-0 text-white"><i class='bx bx-search-alt me-2'></i>RÉFÉRENCEMENT</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="seo_title" class="form-label fw-semibold">Titre SEO</label>
            <input type="text" class="form-control @error('seo_title') is-invalid @enderror" id="seo_title" name="seo_title" maxlength="70" value="{{ old('seo_title', $model?->seo_title) }}">
            @include('admin._field-error', ['field' => 'seo_title'])
            <div class="form-text">60 caractères conseillés. Vide : le nom est utilisé.</div>
        </div>
        <div class="mb-3">
            <label for="seo_description" class="form-label fw-semibold">Méta-description</label>
            <textarea class="form-control @error('seo_description') is-invalid @enderror" id="seo_description" name="seo_description" rows="3" maxlength="170">{{ old('seo_description', $model?->seo_description) }}</textarea>
            @include('admin._field-error', ['field' => 'seo_description'])
            <div class="form-text">Texte affiché par Google sous le titre (160 caractères conseillés). Vide : le résumé est utilisé.</div>
        </div>
        @include('admin._media-picker', ['name' => 'seo_media_id', 'id' => 'seo_media_id', 'label' => 'Image de partage', 'selected' => [old('seo_media_id') ? \App\Models\Media::find(old('seo_media_id')) : $model?->seoImage], 'upload' => null, 'help' => 'Aperçu lors d\'un partage (WhatsApp, Facebook). Vide : la première image du contenu.'])
    </div>
</div>
