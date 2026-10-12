{{-- Témoignage écrit. --}}
<figure class="testimonial mb-0 h-100">
    @if ($temoignage->rating)
        <div class="stars mb-2" aria-label="Note : {{ $temoignage->rating }} sur 5">@for ($i = 1; $i <= 5; $i++)<i class='bx {{ $i <= $temoignage->rating ? 'bxs-star' : 'bx-star' }}' aria-hidden="true"></i>@endfor</div>
    @endif
    <blockquote>{{ $temoignage->content }}</blockquote>
    <figcaption class="d-flex align-items-center gap-2">
        @if ($temoignage->photo)
            <img src="{{ $temoignage->photo->thumbUrl() }}" alt="" width="44" height="44" class="testimonial-photo" loading="lazy">
        @endif
        <span>
            <strong>{{ $temoignage->author_name }}</strong>
            <span>{{ collect([$temoignage->author_title, $temoignage->organization])->filter()->implode(' — ') }}</span>
        </span>
    </figcaption>
</figure>
