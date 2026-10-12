{{-- Résultats d'un partenaire : chiffres et visites des 30 derniers jours (barres en CSS, valeurs au survol). $stats --}}
@php
    $max = max(1, $stats['series']->max('visits'));
    $tiles = [
        ['bx-mouse', 'Visites (30 j)', $stats['visits_period'], 'Depuis le début : '.$stats['visits_total']],
        ['bx-envelope', 'Demandes', $stats['demandes'], 'Devis, contacts, démos'],
        ['bx-cart', 'Ventes boutique', $stats['ventes'], \App\Models\Materiel::money($stats['chiffre'])],
        ['bx-wallet', 'Commissions à payer', \App\Models\Materiel::money($stats['a_payer']), 'Déjà payées : '.\App\Models\Materiel::money($stats['payees'])],
    ];
@endphp
<div class="row g-3 mb-3">
    @foreach ($tiles as [$icon, $label, $value, $hint])
        <div class="col-sm-6 col-xl-3">
            <div class="nv-panel h-100">
                <div class="small text-muted"><i class='bx {{ $icon }} me-1'></i>{{ $label }}</div>
                <div class="h4 mb-0">{{ $value }}</div>
                <div class="small text-muted">{{ $hint }}</div>
            </div>
        </div>
    @endforeach
</div>
<div class="nv-panel mb-3">
    <div class="small text-muted mb-2">Visites venues du lien, jour par jour ({{ $stats['days'] }} derniers jours)</div>
    <div class="visit-bars" role="img" aria-label="Visites par jour : {{ $stats['visits_period'] }} au total">
        @foreach ($stats['series'] as $point)
            <span style="height: {{ max(3, (int) round($point['visits'] / $max * 100)) }}%" class="{{ $point['visits'] ? '' : 'empty' }}" title="{{ $point['day']->translatedFormat('j M') }} : {{ $point['visits'] }} visite(s)"></span>
        @endforeach
    </div>
    <div class="d-flex justify-content-between small text-muted mt-1"><span>{{ $stats['series']->first()['day']->translatedFormat('j M') }}</span><span>Aujourd'hui</span></div>
</div>
@once
    @push('scripts')
        <style>
            .visit-bars { display: flex; align-items: flex-end; gap: 3px; height: 110px; }
            .visit-bars span { flex: 1; background: linear-gradient(180deg, #2f7bff, #1d4e89); border-radius: 3px 3px 0 0; min-width: 4px; }
            .visit-bars span.empty { background: #e3e8f2; }
            .visit-bars span:hover { background: #f97316; }
        </style>
    @endpush
@endonce
