@extends('layouts.admin')

@section('title', 'Mon espace partenaire')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Réseaux sociaux</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mon espace partenaire</li>
                </ol>
            </nav>
        </div>
        @can('partenaires.kit')
            <div class="ms-auto"><a href="{{ route('kit.index') }}" class="btn btn-primary btn-sm"><i class='bx bx-share me-1'></i>Kit de publication</a></div>
        @endcan
    </div>
    <hr />

    @if (! $partenaire)
        <div class="alert alert-info">Votre compte n'est pas encore relié à une fiche partenaire. Demandez à l'administrateur de CSB de l'associer (Partenaires → Modifier → Compte).</div>
    @else
        <div class="card">
            <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-trending-up me-2'></i>BONJOUR {{ mb_strtoupper($partenaire->name) }}</h6></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-lg-7">
                        <label class="form-label small fw-semibold" for="my-link">Votre lien personnel (à mettre dans vos publications et votre bio)</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="my-link" value="{{ $partenaire->link() }}" readonly>
                            <button class="btn btn-outline-primary" type="button" data-copy="#my-link"><i class='bx bx-copy'></i> Copier</button>
                        </div>
                        <div class="form-text">Le kit de publication ajoute ce lien automatiquement à tous les textes.</div>
                    </div>
                    <div class="col-lg-5 small">
                        @if ($partenaire->promoActive())
                            <div class="nv-panel"><strong>Votre code promo : {{ $partenaire->promo_code }}</strong><br>-{{ rtrim(rtrim(number_format($partenaire->promo_discount, 2, ',', ''), '0'), ',') }} % pour vos abonnés sur la boutique{{ $partenaire->promo_ends_on ? ', jusqu\'au '.$partenaire->promo_ends_on->format('d/m/Y') : '' }}.</div>
                        @endif
                        <div class="mt-2"><strong>Vos commissions :</strong> @foreach (\App\Enums\CommissionKind::cases() as $kind){{ $kind->label() }} {{ rtrim(rtrim(number_format($partenaire->rateFor($kind), 2, ',', ''), '0'), ',') }} %{{ $loop->last ? '' : ' · ' }}@endforeach du montant encaissé, pour chaque vente faite dans les {{ $partenaire->cookie_days }} jours après un clic sur votre lien.</div>
                    </div>
                </div>
                @include('partenaires._stats', ['stats' => $stats])
            </div>
        </div>
        <div class="card">
            <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-wallet me-2'></i>MES COMMISSIONS</h6></div>
            <div class="card-body">
                @include('partenaires._commissions', ['commissions' => $commissions, 'payable' => false, 'formId' => null])
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => {
            const input = document.querySelector(b.dataset.copy);
            input.select();
            (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.resolve(document.execCommand('copy'))).then(() => { b.innerHTML = "<i class='bx bx-check'></i> Copié"; });
        }));
    </script>
@endpush
