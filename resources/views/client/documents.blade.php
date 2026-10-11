@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Mes documents'])
        <div class="detail-panel">
            @include('client._documents-list', ['documents' => $documents])
            <div class="mt-3">{{ $documents->links() }}</div>
        </div>
    @endcomponent
@endsection
