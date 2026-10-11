@extends('layouts.admin')

@section('title', 'Alertes WhatsApp')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Paramètres</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Alertes WhatsApp</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-9">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bxl-whatsapp me-2'></i>ALERTES WHATSAPP À L'ÉQUIPE</h6>
                    @if ($configured)
                        <span class="badge bg-success">API configurée</span>
                    @else
                        <span class="badge bg-warning text-dark">API non configurée</span>
                    @endif
                </div>
                <div class="card-body">
                    @unless ($configured)
                        <div class="alert alert-warning small">
                            <strong>Les alertes ne partent pas encore.</strong> Il faut un compte <strong>WhatsApp Business</strong> (Meta) et, dans le fichier <code>.env</code> du serveur :
                            <code>WHATSAPP_TOKEN</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code> et le nom d'un <strong>modèle de message approuvé</strong> par Meta avec un paramètre de texte (<code>WHATSAPP_TEMPLATE</code>, par défaut « csb_alerte », ex. « Alerte CSB : @{{1}} »).
                            Vous pouvez déjà enregistrer les numéros ci-dessous.
                        </div>
                    @endunless
                    <form method="POST" action="{{ route('whatsapp.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="recipients" class="form-label fw-semibold">Numéros à prévenir</label>
                                <textarea class="form-control @error('recipients') is-invalid @enderror" id="recipients" name="recipients" rows="5" maxlength="500" placeholder="74 74 56 69&#10;67 20 57 36">{{ old('recipients', $recipients) }}</textarea>
                                @error('recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Un numéro par ligne ; +223 ajouté aux numéros à 8 chiffres.</div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-label fw-semibold">Prévenir pour</div>
                                @foreach (\App\Http\Controllers\Web\Admin\WhatsAppController::EVENTS as $key => $label)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="events[]" value="{{ $key }}" id="event-{{ $key }}" @checked(in_array($key, old('events', $events), true))>
                                        <label class="form-check-label" for="event-{{ $key }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('whatsapp.test') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-success" @disabled(! $configured)><i class='bx bx-send me-1'></i>Envoyer un message de test au premier numéro</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-error me-2'></i>ÉCHECS RÉCENTS</h6></div>
                <div class="card-body">
                    @forelse ($failures as $log)
                        <div class="small border-bottom py-2"><span class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</span> · {{ $log->description }}<div class="text-danger text-break">{{ $log->new_values['error'] ?? '' }}</div></div>
                    @empty
                        <p class="text-muted small mb-0">Aucun échec d'envoi.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
