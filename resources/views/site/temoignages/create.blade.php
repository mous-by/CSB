@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Partager mon expérience', 'crumb' => 'Témoignages', 'intro' => 'Une vidéo filmée au téléphone suffit : présentez-vous, dites ce que CSB a fait pour vous et ce que vous en pensez.'])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    @if (session('temoignage_sent'))
                        <div class="alert alert-success d-flex gap-3 align-items-start" role="status">
                            <i class='bx bx-heart fs-2' aria-hidden="true"></i>
                            <div><strong>Merci pour votre témoignage !</strong><br>Nous le relisons et le publions très bientôt sur le site.</div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('site.temoignages.store') }}" enctype="multipart/form-data" class="form-card" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                @include('partials.video-upload', ['url' => route('site.temoignages.video'), 'id' => 'f-video', 'label' => 'Votre vidéo (conseillé)'])
                            </div>
                            <div class="col-12">@include('site.demandes._field', ['name' => 'content', 'label' => 'Votre témoignage écrit', 'type' => 'textarea', 'max' => 1500, 'rows' => 4, 'help' => 'Facultatif si vous envoyez une vidéo.', 'placeholder' => 'Ce que CSB a fait pour vous, ce que vous en avez pensé…'])</div>
                            <div class="col-12">
                                <fieldset>
                                    <legend class="form-label">Votre note</legend>
                                    <div class="star-input" role="radiogroup">
                                        @for ($i = 5; $i >= 1; $i--)
                                            <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating') === $i)>
                                            <label for="rating-{{ $i }}" title="{{ $i }} sur 5"><i class='bx bxs-star' aria-hidden="true"></i><span class="visually-hidden">{{ $i }} sur 5</span></label>
                                        @endfor
                                    </div>
                                </fieldset>
                            </div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'author_name', 'label' => 'Votre nom', 'required' => true, 'max' => 150, 'value' => $client?->name, 'autocomplete' => 'name'])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'contact', 'label' => 'Téléphone ou e-mail (non publié)', 'required' => true, 'max' => 150, 'value' => $client?->phone ?? $client?->email])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'organization', 'label' => 'Entreprise, école ou établissement', 'max' => 150, 'value' => $client?->organization])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'author_title', 'label' => 'Votre fonction', 'max' => 150, 'placeholder' => 'Directeur, gérante…'])</div>
                            <div class="col-12">
                                <label for="f-photo" class="form-label">Votre photo (facultatif)</label>
                                <input type="file" class="form-control @error('photo_upload') is-invalid @enderror" id="f-photo" name="photo_upload" accept="image/jpeg,image/png,image/webp">
                                @error('photo_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="f-consent" name="consent" value="1" @checked(old('consent')) required>
                                    <label class="form-check-label small" for="f-consent">J'accepte que CSB publie mon témoignage (vidéo, texte, nom, fonction et photo) sur son site et ses réseaux sociaux. Mon téléphone ou e-mail n'est jamais publié.</label>
                                </div>
                                @error('consent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="hp-field" aria-hidden="true"><label for="f-site-web">Ne pas remplir ce champ</label><input type="text" id="f-site-web" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></div>
                            <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN }}" value="{{ \App\Support\SpamGuard::token() }}">
                            <div class="col-12"><button type="submit" class="btn btn-accent btn-lg"><i class='bx bx-send' aria-hidden="true"></i> Envoyer mon témoignage</button></div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="detail-panel">
                        <h2 class="h6"><i class='bx bx-bulb text-orange' aria-hidden="true"></i> Conseils pour votre vidéo</h2>
                        <ul class="small mb-0 ps-3">
                            <li>30 secondes à 2 minutes suffisent.</li>
                            <li>Filmez à l'horizontale, dans un endroit calme et bien éclairé.</li>
                            <li>Présentez-vous : nom, fonction, entreprise ou école.</li>
                            <li>Dites ce que CSB a fait pour vous (logiciel, matériel, réparation…) et ce que cela vous a apporté.</li>
                            <li>Gardez votre téléphone stable, proche de vous pour un son clair.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/video-upload.js') }}?v={{ filemtime(public_path('assets/js/video-upload.js')) }}"></script>
@endpush
