@extends('layouts.admin')

@section('title', 'Kit de publication')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Réseaux sociaux</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Kit de publication</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        $groups = [
            ['materiels', 'Matériel', 'bx-laptop', 'materiel', $materiels],
            ['logiciels', 'Logiciels', 'bx-code-block', 'logiciel', $logiciels],
            ['realisations', 'Réalisations', 'bx-briefcase-alt-2', 'realisation', $realisations],
            ['articles', 'Actualités', 'bx-news', 'article', $articles],
        ];
    @endphp
    <div class="card">
        <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-share me-2'></i>CHOISISSEZ CE QUE VOUS VOULEZ PUBLIER</h6></div>
        <div class="card-body">
            <p class="text-muted small">Chaque contenu a ses textes prêts à copier (Facebook, Instagram, TikTok, WhatsApp, LinkedIn){{ $partenaire ? ', avec votre lien personnel' : '' }}, et ses visuels prêts à poster (carré et story).</p>
            <ul class="nav nav-pills mb-3" role="tablist">
                @foreach ($groups as [$key, $label, $icon, $type, $items])
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($loop->first) active @endif" data-bs-toggle="pill" data-bs-target="#kit-{{ $key }}" type="button" role="tab"><i class='bx {{ $icon }} me-1'></i>{{ $label }} <span class="badge bg-light text-dark">{{ $items->count() }}</span></button>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach ($groups as [$key, $label, $icon, $type, $items])
                    <div class="tab-pane fade @if ($loop->first) show active @endif" id="kit-{{ $key }}" role="tabpanel">
                        <div class="row g-3">
                            @forelse ($items as $item)
                                @php
                                    $visual = match ($type) { 'materiel' => $item->images()->first(), 'logiciel' => $item->gallery->first() ?? $item->logo, 'realisation' => $item->images()->first(), default => $item->cover };
                                    $title = $item->designation ?? $item->name ?? $item->title;
                                @endphp
                                <div class="col-sm-6 col-lg-4 col-xxl-3">
                                    <a href="{{ route('kit.show', [$type, $item->id]) }}" class="kit-tile">
                                        <span class="kit-thumb">@if ($visual)<img src="{{ $visual->thumbUrl() }}" alt="" loading="lazy">@else<i class='bx {{ $icon }}'></i>@endif
                                            @if ($type === 'materiel' && $item->isOnPromo())<span class="badge bg-danger">Promo -{{ $item->promoPercent() }} %</span>@endif
                                        </span>
                                        <strong>{{ $title }}</strong>
                                        <span class="small text-primary"><i class='bx bx-share'></i> Préparer la publication</span>
                                    </a>
                                </div>
                            @empty
                                <p class="text-muted small">Rien de publié pour l'instant.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .kit-tile { display: flex; flex-direction: column; gap: .35rem; height: 100%; padding: .7rem; border: 1px solid #e3e8f2; border-radius: 12px; text-decoration: none; color: inherit; transition: box-shadow .2s, transform .2s; }
        .kit-tile:hover { box-shadow: 0 10px 24px rgba(29, 78, 137, .15); transform: translateY(-2px); }
        .kit-thumb { position: relative; display: flex; align-items: center; justify-content: center; aspect-ratio: 16 / 10; background: #f3f6fb; border-radius: 8px; overflow: hidden; font-size: 2.4rem; color: #9fb4e0; }
        .kit-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .kit-thumb .badge { position: absolute; top: 6px; left: 6px; }
    </style>
@endpush
