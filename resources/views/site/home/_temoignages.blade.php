<section class="section section-alt">
    <div class="container">
        <div class="section-title text-center">
            <span class="eyebrow">Ils nous font confiance</span>
            <h2>Ce que disent nos clients</h2>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach ($temoignages as $temoignage)
                <div class="col-md-6 col-lg-4">
                    <figure class="testimonial mb-0">
                        <blockquote>{{ $temoignage->content }}</blockquote>
                        <figcaption>
                            <strong>{{ $temoignage->author_name }}</strong>
                            <span>{{ collect([$temoignage->author_title, $temoignage->organization])->filter()->implode(' — ') }}</span>
                        </figcaption>
                    </figure>
                </div>
            @endforeach
        </div>
    </div>
</section>
