@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Bonjour '.\Illuminate\Support\Str::before($client->name, ' ')])
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><a href="{{ route('client.demandes') }}" class="client-stat"><i class='bx bx-envelope' aria-hidden="true"></i><strong>{{ $counts['demandes'] }}</strong><span>Demandes · {{ $counts['ouvertes'] }} en cours</span></a></div>
            @foreach ($extras as $extra)
                <div class="col-sm-6 col-xl-3"><a href="{{ route($extra['route']) }}" class="client-stat"><i class='bx {{ $extra['icon'] }}' aria-hidden="true"></i><strong>{{ $extra['count'] }}</strong><span>{{ $extra['title'] }} · {{ $extra['open'] }} {{ $extra['label'] }}</span></a></div>
            @endforeach
            <div class="col-sm-6 col-xl-3"><a href="{{ route('client.documents') }}" class="client-stat"><i class='bx bx-folder' aria-hidden="true"></i><strong>{{ $counts['documents'] }}</strong><span>Documents</span></a></div>
        </div>

        <div class="detail-panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Mes dernières demandes</h2>
                <a href="{{ route('site.devis') }}" class="btn btn-accent btn-sm">Nouvelle demande de devis</a>
            </div>
            @include('client._demandes-table', ['demandes' => $demandes])
        </div>

        @if ($documents->isNotEmpty())
            <div class="detail-panel">
                <h2 class="h5">Derniers documents</h2>
                @include('client._documents-list', ['documents' => $documents])
            </div>
        @endif
    @endcomponent
@endsection
