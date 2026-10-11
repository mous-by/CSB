@extends('layouts.site')

{{-- Message simple (confirmation, lien expiré…). $title, $message. --}}
@section('content')
    @include('site.partials.page-hero', ['title' => $title])
    <section class="section">
        <div class="container">
            <div class="form-success mx-auto" style="max-width: 640px">
                <span class="form-success-icon"><i class='bx bx-check' aria-hidden="true"></i></span>
                <p class="mb-3">{{ $message }}</p>
                <a href="{{ route('site.home') }}" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </section>
@endsection
