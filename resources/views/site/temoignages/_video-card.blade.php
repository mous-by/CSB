{{-- Carte d'un témoignage vidéo : aperçu + bouton lecture ; la vidéo s'ouvre en grand (site.js, [data-video-play]).
     Lien externe (YouTube, Facebook…) : ouvert dans un nouvel onglet, jamais intégré (aucun script externe). --}}
@php
    $poster = $temoignage->posterUrl();
@endphp
<article class="video-testimonial" data-tilt>
    @if ($temoignage->hasVideo())
        <button type="button" class="vt-media" data-video-play="{{ $temoignage->videoUrl() }}" data-video-poster="{{ $poster }}" data-video-title="{{ $temoignage->author_name }}" aria-label="Regarder le témoignage de {{ $temoignage->author_name }}">
    @else
        <a href="{{ $temoignage->video_url }}" class="vt-media" target="_blank" rel="noopener" aria-label="Regarder le témoignage de {{ $temoignage->author_name }} sur {{ $temoignage->videoPlatform() }}">
    @endif
        @if ($poster)
            <img src="{{ $poster }}" alt="" loading="lazy" width="720" height="405">
        @else
            <span class="vt-placeholder"><i class='bx bx-user-voice' aria-hidden="true"></i></span>
        @endif
        <span class="vt-play"><i class='bx bx-play' aria-hidden="true"></i></span>
        @if ($temoignage->durationForDisplay())
            <span class="vt-duration">{{ $temoignage->durationForDisplay() }}</span>
        @elseif (! $temoignage->hasVideo())
            <span class="vt-duration"><i class='bx bx-link-external' aria-hidden="true"></i> {{ $temoignage->videoPlatform() }}</span>
        @endif
    @if ($temoignage->hasVideo())
        </button>
    @else
        </a>
    @endif
    <div class="vt-body">
        @if ($temoignage->rating)
            <div class="stars" aria-label="Note : {{ $temoignage->rating }} sur 5">@for ($i = 1; $i <= 5; $i++)<i class='bx {{ $i <= $temoignage->rating ? 'bxs-star' : 'bx-star' }}' aria-hidden="true"></i>@endfor</div>
        @endif
        @if ($temoignage->content)
            <p class="vt-quote">« {{ \Illuminate\Support\Str::limit($temoignage->content, 160) }} »</p>
        @endif
        <div class="vt-author">
            @if ($temoignage->photo)
                <img src="{{ $temoignage->photo->thumbUrl() }}" alt="" width="40" height="40" loading="lazy">
            @endif
            <span><strong>{{ $temoignage->author_name }}</strong><small>{{ collect([$temoignage->author_title, $temoignage->organization])->filter()->implode(' — ') }}</small></span>
        </div>
    </div>
</article>
