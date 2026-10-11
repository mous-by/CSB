@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => $title])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="prose">{!! $content !!}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
