<section class="section section-alt">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 section-title">
            <div>
                <span class="eyebrow">Actualités et conseils</span>
                <h2>Les dernières nouvelles de CSB</h2>
            </div>
            <a href="{{ route('site.actualites.index') }}" class="btn btn-outline-primary">Toutes les actualités</a>
        </div>
        <div class="row g-4">
            @foreach ($articles as $article)
                <div class="col-md-6 col-lg-4">@include('site.actualites._card', ['article' => $article])</div>
            @endforeach
        </div>
    </div>
</section>
