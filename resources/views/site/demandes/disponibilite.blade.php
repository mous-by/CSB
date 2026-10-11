@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je suis intéressé(e) par « '.$materiel->designation.' ». Est-il disponible ?')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $materiel->price === null ? 'Demander le prix' : 'Confirmer la disponibilité',
        'crumbs' => [['Matériel', route('site.materiels.index')], [$materiel->designation, route('site.materiels.show', $materiel->slug)]],
        'crumb' => 'Disponibilité',
        'intro' => 'Nous vérifions le prix et la disponibilité de ce produit et revenons vers vous rapidement.',
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @component('site.demandes._form', ['action' => route('site.disponibilite.store', $materiel->slug), 'submit' => 'Envoyer la demande'])
                        <div class="col-12">
                            @include('site.demandes._subject', ['image' => $materiel->images()->first(), 'label' => $materiel->designation, 'hint' => $materiel->isOnPromo() ? 'Prix promotionnel : '.$materiel->promoPriceForDisplay().' (au lieu de '.$materiel->priceForDisplay().')' : ($materiel->priceForDisplay() ? 'Prix indicatif : '.$materiel->priceForDisplay() : 'Prix sur demande'), 'url' => route('site.materiels.show', $materiel->slug), 'icon' => 'bx-laptop'])
                        </div>
                        @include('site.demandes._identity')
                        <div class="col-12">@include('site.demandes._field', ['name' => 'message', 'label' => 'Précisions', 'type' => 'textarea', 'max' => 2000, 'rows' => 4, 'placeholder' => 'Quantité souhaitée, configuration, délai…'])</div>
                    @endcomponent
                </div>
            </div>
        </div>
    </section>
@endsection
