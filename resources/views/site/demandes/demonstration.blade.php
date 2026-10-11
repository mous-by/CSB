@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite une démonstration du logiciel « '.$logiciel->name.' ».')

@section('content')
    @include('site.partials.page-hero', [
        'title' => 'Demander une démonstration',
        'crumbs' => [['Logiciels', route('site.logiciels.index')], [$logiciel->name, route('site.logiciels.show', $logiciel->slug)]],
        'crumb' => 'Démonstration',
        'intro' => 'Découvrez '.$logiciel->name.' en situation réelle : nous vous le présentons selon vos disponibilités.',
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @component('site.demandes._form', ['action' => route('site.demonstration.store', $logiciel->slug), 'submit' => 'Demander la démonstration'])
                        <div class="col-12">
                            @include('site.demandes._subject', ['image' => $logiciel->logo ?? $logiciel->gallery->first(), 'label' => $logiciel->name, 'hint' => 'Démonstration du logiciel', 'url' => route('site.logiciels.show', $logiciel->slug), 'icon' => 'bx-code-block'])
                        </div>
                        @include('site.demandes._identity')
                        <div class="col-12">@include('site.demandes._field', ['name' => 'message', 'label' => 'Votre besoin', 'type' => 'textarea', 'required' => true, 'max' => 3000, 'rows' => 5, 'help' => 'Votre activité, le nombre d\'utilisateurs, ce que vous attendez du logiciel…'])</div>
                        <div class="col-12">@include('site.demandes._field', ['name' => 'availability', 'label' => 'Vos disponibilités', 'max' => 300, 'placeholder' => 'Ex. mardi ou jeudi matin'])</div>
                    @endcomponent
                </div>
            </div>
        </div>
    </section>
@endsection
