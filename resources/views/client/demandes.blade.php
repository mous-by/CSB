@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Mes demandes'])
        <div class="detail-panel">
            @include('client._demandes-table', ['demandes' => $demandes])
            <div class="mt-3">{{ $demandes->links() }}</div>
        </div>
    @endcomponent
@endsection
