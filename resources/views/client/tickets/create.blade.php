@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Nouvelle demande d\'assistance'])
        <form method="POST" action="{{ route('client.tickets.store') }}" enctype="multipart/form-data" class="form-card" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-12">@include('site.demandes._field', ['name' => 'subject', 'label' => 'Objet', 'required' => true, 'max' => 200, 'placeholder' => 'Ex. Mon ordinateur ne démarre plus'])</div>
                <div class="col-md-6">@include('site.demandes._field', ['name' => 'category', 'label' => 'Type de demande', 'type' => 'select', 'required' => true, 'options' => \App\Enums\TicketCategory::options()])</div>
                <div class="col-md-6">@include('site.demandes._field', ['name' => 'priority', 'label' => 'Urgence', 'type' => 'select', 'required' => true, 'options' => \App\Enums\TicketPriority::options(), 'value' => 'normale'])</div>
                <div class="col-12">@include('site.demandes._field', ['name' => 'device', 'label' => 'Appareil ou logiciel concerné', 'max' => 200, 'placeholder' => 'Ex. HP ProBook 450, imprimante Canon, KalanNet…'])</div>
                <div class="col-12">@include('site.demandes._field', ['name' => 'message', 'label' => 'Description du problème', 'type' => 'textarea', 'required' => true, 'max' => 10000, 'rows' => 6, 'help' => 'Que se passe-t-il ? Depuis quand ? Message d\'erreur éventuel…'])</div>
                <div class="col-12">
                    <label for="f-attachment" class="form-label">Pièce jointe (photo, capture d'écran…)</label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="f-attachment" name="attachment">
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">10 Mo au plus.</div>
                </div>
                <div class="col-12"><button type="submit" class="btn btn-accent btn-lg"><i class='bx bx-send' aria-hidden="true"></i> Envoyer la demande</button></div>
            </div>
        </form>
    @endcomponent
@endsection
