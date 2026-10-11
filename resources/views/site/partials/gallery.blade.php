{{-- Galerie d'images d'une fiche : image principale et vignettes (sans bibliothèque externe). $images : collection de Media. --}}
@if ($images->isNotEmpty())
    <div class="gallery" data-gallery>
        <div class="gallery-main">
            <img src="{{ $images->first()->url() }}" alt="{{ $images->first()->altText() }}" width="{{ $images->first()->width }}" height="{{ $images->first()->height }}" data-gallery-main>
        </div>
        @if ($images->count() > 1)
            <div class="gallery-thumbs" role="list">
                @foreach ($images as $index => $image)
                    <button type="button" role="listitem" class="{{ $index === 0 ? 'active' : '' }}" data-src="{{ $image->url() }}" data-alt="{{ $image->altText() }}" aria-label="Afficher l'image {{ $index + 1 }} : {{ $image->altText() }}">
                        <img src="{{ $image->thumbUrl() }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
                    var main = gallery.querySelector('[data-gallery-main]');
                    gallery.querySelectorAll('.gallery-thumbs button').forEach(function (button) {
                        button.addEventListener('click', function () {
                            main.src = button.dataset.src;
                            main.alt = button.dataset.alt;
                            gallery.querySelectorAll('.gallery-thumbs button').forEach(function (b) { b.classList.toggle('active', b === button); });
                        });
                    });
                });
            </script>
        @endpush
    @endonce
@endif
