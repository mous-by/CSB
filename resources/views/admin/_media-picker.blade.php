{{-- Sélecteur d'images d'un formulaire de contenu : recherche dans la bibliothèque (Select2, vignettes) et envoi direct
     de nouvelles images, enregistrées dans la bibliothèque par MediaService à l'enregistrement du contenu.
     Paramètres : name (champ : « logo_media_id » ou « gallery » pour une galerie), label, multiple (bool), selected
     (collection de Media déjà choisies), upload (nom du champ fichier, ex. « logo_upload » ; null = pas d'envoi direct),
     id (identifiant unique dans la page), help (facultatif). --}}
@php
    $multiple = $multiple ?? false;
    $selected = collect($selected ?? [])->filter();
    $fieldName = $multiple ? $name.'[]' : $name;
    $errorKey = $errors->has($name) ? $name : ($errors->has($name.'.*') ? $name.'.*' : null);
    $uploadErrors = $upload ? collect($errors->getMessages())->filter(fn ($messages, $key) => $key === $upload || str_starts_with($key, $upload.'.'))->flatten()->unique() : collect();
@endphp
<div class="media-picker">
    <label for="{{ $id }}" class="form-label fw-semibold">{{ $label }}</label>
    <select class="form-select media-select" id="{{ $id }}" name="{{ $fieldName }}" @if ($multiple) multiple @endif
            data-search-url="{{ route('medias.search') }}" data-placeholder="{{ $multiple ? 'Choisir des images dans la bibliothèque…' : 'Choisir une image…' }}">
        @unless ($multiple)
            <option value=""></option>
        @endunless
        @foreach ($selected as $media)
            <option value="{{ $media->id }}" data-thumb="{{ $media->thumbUrl() }}" selected>{{ $media->alt ?: $media->original_name }}</option>
        @endforeach
    </select>
    @if ($errorKey)<div class="text-danger small mt-1">{{ $errors->first($errorKey) }}</div>@endif
    @if ($upload && auth()->user()->can('create', \App\Models\Media::class))
        <div class="input-group input-group-sm mt-2">
            <span class="input-group-text"><i class='bx bx-upload'></i></span>
            <input type="file" class="form-control @if ($uploadErrors->isNotEmpty()) is-invalid @endif" name="{{ $multiple ? $upload.'[]' : $upload }}" accept="image/jpeg,image/png,image/webp" @if ($multiple) multiple @endif aria-label="Envoyer {{ $multiple ? 'de nouvelles images' : 'une nouvelle image' }}">
        </div>
        @foreach ($uploadErrors as $message)
            <div class="text-danger small mt-1">{{ $message }}</div>
        @endforeach
    @endif
    <div class="form-text">{{ $help ?? ($multiple ? 'L\'ordre de sélection est l\'ordre d\'affichage. Vous pouvez aussi envoyer de nouvelles images.' : 'Choisissez une image de la bibliothèque ou envoyez-en une nouvelle.') }}</div>
</div>

@once
    @push('scripts')
        <script>
            // Sélecteurs d'images : recherche AJAX dans la bibliothèque, vignette dans la liste et dans la sélection.
            $(function () {
                const render = (item) => {
                    if (!item.id) return item.text;
                    const thumb = item.thumb || $(item.element).data('thumb');
                    const label = $('<span>').text(item.text).html();
                    return $('<span class="d-inline-flex align-items-center gap-2">' + (thumb ? '<img src="' + thumb + '" alt="" style="width:40px;height:30px;object-fit:cover;border-radius:4px">' : '') + label + '</span>');
                };
                $('.media-select').each(function () {
                    const modal = $(this).closest('.modal');
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        allowClear: !this.multiple,
                        placeholder: $(this).data('placeholder'),
                        dropdownParent: modal.length ? modal : $(document.body),
                        templateResult: render,
                        templateSelection: render,
                        ajax: {
                            url: $(this).data('search-url'),
                            dataType: 'json',
                            delay: 250,
                            data: (params) => ({ q: params.term || '' }),
                            processResults: (data) => ({ results: data.results }),
                            cache: true,
                        },
                    });
                });
            });
        </script>
    @endpush
@endonce
