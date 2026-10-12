@php
    $videoItems = $temoignages->filter->isVideo()->take(3);
    $textItems = $temoignages->reject->isVideo()->filter(fn ($t) => filled($t->content))->take($videoItems->isEmpty() ? 6 : 3);
@endphp
<section class="section section-alt">
    <div class="container">
        <div class="section-title text-center">
            <span class="eyebrow">Ils nous font confiance</span>
            <h2>Ce que disent nos clients</h2>
        </div>
        @if ($videoItems->isNotEmpty())
            <div class="row g-4 justify-content-center mb-4">
                @foreach ($videoItems as $temoignage)
                    <div class="col-md-6 col-lg-4">@include('site.temoignages._video-card')</div>
                @endforeach
            </div>
        @endif
        @if ($textItems->isNotEmpty())
            <div class="row g-4 justify-content-center">
                @foreach ($textItems as $temoignage)
                    <div class="col-md-6 col-lg-4">@include('site.temoignages._text-card')</div>
                @endforeach
            </div>
        @endif
        <div class="text-center mt-4 d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route('site.temoignages.index') }}" class="btn btn-outline-primary">Tous les témoignages</a>
            <a href="{{ route('site.temoignages.create') }}" class="btn btn-accent"><i class='bx bx-video-recording' aria-hidden="true"></i> Partager mon expérience</a>
        </div>
    </div>
</section>
