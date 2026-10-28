@extends('layouts.admin')

@section('title', 'Statistiques du site')

@section('content')
    @php
        $trend = fn (?int $change) => $change === null ? 'pas de comparaison possible' : ($change >= 0 ? '+' : '').$change.' % par rapport à la période précédente';
    @endphp
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Statistiques du site</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto nv-chips">
            @foreach (\App\Services\Statistiques\SiteStats::PERIODS as $days => $label)
                <a href="{{ route('statistiques.index', ['periode' => $days]) }}" class="nv-chip {{ $report['days'] === $days ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <hr />

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        @foreach ([
            ['Visites', number_format($report['visits'], 0, ',', ' '), $trend($report['visits_change']), 'bx bx-group', 'primary', false],
            ['Pages vues', number_format($report['views'], 0, ',', ' '), $report['views_per_visit'].' page(s) par visite', 'bx bx-show', 'info', false],
            ['Comptes clients créés', (string) $report['clients'], $report['clients_total'].' au total · '.$trend($report['clients_change']), 'bx bx-user-plus', 'success', false],
            ['Demandes et commandes', (string) ($report['demandes'] + $report['commandes']), $report['conversion'] === null ? 'aucune visite à comparer' : $report['conversion'].' % des visites ('.$report['demandes'].' demandes, '.$report['commandes'].' commandes)', 'bx bx-envelope', 'warning', true],
        ] as [$cardLabel, $cardValue, $cardSub, $cardIcon, $cardColor, $cardDark])
            <div class="col mb-3">
                <div class="card radius-10 bg-{{ $cardColor }} bg-gradient h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 {{ $cardDark ? 'text-dark' : 'text-white' }}">{{ $cardLabel }}</p>
                                <h4 class="my-1 {{ $cardDark ? 'text-dark' : 'text-white' }}">{{ $cardValue }}</h4>
                                <p class="mb-0 small {{ $cardDark ? 'text-dark' : 'text-white' }}">{{ $cardSub }}</p>
                            </div>
                            <div class="{{ $cardDark ? 'text-dark' : 'text-white' }} ms-auto font-35"><i class='{{ $cardIcon }}'></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('dashboard._activity-chart', ['chart' => $report['chart']])

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="text-white mb-0"><i class='bx bx-file me-2'></i>PAGES LES PLUS VUES</h6></div>
                <div class="card-body">
                    @if (empty($report['pages']))
                        <p class="text-muted mb-0">Aucune page vue sur cette période.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>PAGE</th><th class="text-end">VUES</th><th class="text-end">VISITES</th></tr></thead>
                                <tbody>
                                    @foreach ($report['pages'] as $page)
                                        <tr>
                                            <td class="text-break">{{ $page['label'] }}@if ($page['label'] !== $page['path']) <span class="small text-muted">{{ $page['path'] }}</span>@endif</td>
                                            <td class="text-end">{{ $page['views'] }}</td>
                                            <td class="text-end">{{ $page['visits'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            @foreach ([['D\'OÙ VIENNENT LES VISITEURS', 'bx bx-directions', $report['sources']], ['APPAREILS UTILISÉS', 'bx bx-devices', $report['devices']]] as [$boxTitle, $boxIcon, $rows])
                <div class="card">
                    <div class="card-header card-header-brand"><h6 class="text-white mb-0"><i class='{{ $boxIcon }} me-2'></i>{{ $boxTitle }}</h6></div>
                    <div class="card-body">
                        @forelse ($rows as $row)
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small"><span>{{ $row['label'] }}</span><strong>{{ $row['total'] }} · {{ $row['percent'] }} %</strong></div>
                                <div class="progress" style="height: 8px" role="presentation"><div class="progress-bar" style="width: {{ $row['percent'] }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Aucune visite sur cette période.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="text-white mb-0"><i class='bx bx-link-external me-2'></i>SITES QUI ENVOIENT DES VISITEURS</h6></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        @forelse ($report['referrers'] as $host => $total)
                            <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-break">{{ $host }}</span><strong>{{ $total }}</strong></li>
                        @empty
                            <li class="text-muted">Aucun pour le moment (les visites directes et WhatsApp n'indiquent souvent pas leur origine).</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                    <h6 class="text-white mb-0"><i class='bx bx-user-plus me-2'></i>DERNIERS COMPTES CLIENTS</h6>
                    @if (Route::has('clients.index') && auth()->user()->can('clients.view'))
                        <a href="{{ route('clients.index') }}" class="btn btn-light btn-sm">Tous les clients</a>
                    @endif
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        @forelse ($report['recent_clients'] as $client)
                            <li class="d-flex justify-content-between gap-2 py-1 border-bottom">
                                <span class="text-break">{{ $client->name }}@if ($client->organization) <span class="text-muted">· {{ $client->organization }}</span>@endif</span>
                                <span class="text-muted text-nowrap">{{ $client->created_at->format('d/m/Y') }}</span>
                            </li>
                        @empty
                            <li class="text-muted">Aucun compte client pour le moment.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-muted"><i class='bx bx-info-circle me-1'></i>Une visite = une personne un jour donné (elle peut voir plusieurs pages). Les robots, les aperçus de liens (WhatsApp, Facebook) et les membres de l'équipe connectés ne sont pas comptés. Aucune donnée personnelle n'est conservée : le visiteur est une empreinte anonyme qui change chaque jour.</p>
@endsection
