@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite un devis.')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Demander un devis', 'intro' => 'Décrivez votre besoin : nous l\'étudions et vous adressons une proposition adaptée, sans engagement.'])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    @component('site.demandes._form', ['action' => route('site.devis.store'), 'submit' => 'Envoyer la demande de devis', 'multipart' => true])
                        @if ($subjectModel)
                            <div class="col-12">
                                @if ($subjectModel instanceof \App\Models\Logiciel)
                                    @include('site.demandes._subject', ['image' => $subjectModel->logo ?? $subjectModel->gallery->first(), 'label' => $subjectModel->name, 'hint' => 'Devis pour le logiciel', 'url' => route('site.logiciels.show', $subjectModel->slug), 'icon' => 'bx-code-block'])
                                    <input type="hidden" name="logiciel" value="{{ $subjectModel->slug }}">
                                @else
                                    @include('site.demandes._subject', ['image' => $subjectModel->images()->first(), 'label' => $subjectModel->designation, 'hint' => 'Devis pour le matériel', 'url' => route('site.materiels.show', $subjectModel->slug), 'icon' => 'bx-laptop'])
                                    <input type="hidden" name="materiel" value="{{ $subjectModel->slug }}">
                                @endif
                            </div>
                        @endif
                        @include('site.demandes._identity')
                        <div class="col-md-6">@include('site.demandes._field', ['name' => 'need_type', 'label' => 'Type de besoin', 'type' => 'select', 'required' => true, 'options' => \App\Enums\NeedType::options(), 'value' => $need?->value])</div>
                        <div class="col-md-3">@include('site.demandes._field', ['name' => 'budget', 'label' => 'Budget', 'max' => 100, 'placeholder' => 'Facultatif'])</div>
                        <div class="col-md-3">@include('site.demandes._field', ['name' => 'deadline', 'label' => 'Délai souhaité', 'max' => 100, 'placeholder' => 'Facultatif'])</div>
                        <div class="col-12">@include('site.demandes._field', ['name' => 'message', 'label' => 'Description du besoin', 'type' => 'textarea', 'required' => true, 'max' => 5000, 'rows' => 7, 'value' => $besoin ? 'Service : '.$besoin."\n\n" : null, 'help' => 'Contexte, quantités, fonctionnalités attendues, contraintes… Plus votre description est précise, plus notre proposition le sera.'])</div>
                        <div class="col-12">
                            <label for="f-attachment" class="form-label">Pièce jointe</label>
                            <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="f-attachment" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.jpg,.jpeg,.png" aria-describedby="f-attachment-help">
                            @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-text" id="f-attachment-help">Facultatif : cahier des charges, liste de matériel… PDF, Word, Excel, OpenDocument, JPEG ou PNG, {{ \App\Http\Requests\Site\DemandeRequest::ATTACHMENT_MAX_KB / 1024 }} Mo au plus.</div>
                        </div>
                    @endcomponent
                </div>
                <aside class="col-lg-4">
                    <div class="detail-aside">
                        <div class="detail-panel">
                            <h2 class="h5">Comment ça se passe ?</h2>
                            <ol class="steps mt-3">
                                <li>Vous décrivez votre besoin.</li>
                                <li>Nous vous recontactons pour le préciser si nécessaire.</li>
                                <li>Vous recevez une proposition chiffrée.</li>
                            </ol>
                        </div>
                        @if ($site->whatsappLink())
                            <div class="detail-panel">
                                <h2 class="h6">Plus rapide ?</h2>
                                <p class="small text-muted">Écrivez-nous directement sur WhatsApp.</p>
                                <a href="{{ $site->whatsappLink('Bonjour CSB, je souhaite un devis.') }}" target="_blank" rel="noopener" class="btn btn-whatsapp w-100"><i class='bx bxl-whatsapp' aria-hidden="true"></i> WhatsApp</a>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
