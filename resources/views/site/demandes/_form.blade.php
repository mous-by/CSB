{{-- Cadre d'un formulaire public : succès (envoi sans JavaScript), résumé des erreurs, envoi AJAX (data-ajax-form).
     $action, $slot (champs), $submit, $multipart. --}}
@if (session('demande_sent'))
    <div class="form-success" role="status" tabindex="-1" id="form-success">
        <span class="form-success-icon"><i class='bx bx-check' aria-hidden="true"></i></span>
        <h2 class="h4">{{ session('demande_sent')['title'] }}</h2>
        <p class="mb-0">{{ session('demande_sent')['message'] }}</p>
    </div>
@else
    <form method="POST" action="{{ $action }}" class="form-card" data-ajax-form novalidate @if ($multipart ?? false) enctype="multipart/form-data" @endif>
        @csrf
        <div class="alert alert-danger" data-error-summary tabindex="-1" @if (! $errors->any()) hidden @endif role="alert">
            <i class='bx bx-error-circle' aria-hidden="true"></i> Votre demande n'a pas été envoyée : vérifiez les champs signalés. Vos informations sont conservées.
        </div>
        <div class="row g-3">
            {{ $slot }}
            @include('site.demandes._footer', ['submit' => $submit ?? 'Envoyer'])
        </div>
    </form>
@endif
