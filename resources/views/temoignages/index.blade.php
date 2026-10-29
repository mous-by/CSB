@extends('layouts.admin')

@section('title', 'Témoignages')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Témoignages</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto"><a href="{{ route('site.temoignages.index') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class='bx bx-link-external me-1'></i>Voir la page du site</a></div>
    </div>
    <hr />

    @php
        $moderation = $temoignages->filter->awaitingModeration()->count();
    @endphp
    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-message-square-dots me-2'></i>TÉMOIGNAGES DE CLIENTS</h6>
            @can('create', \App\Models\Temoignage::class)
                <div class="d-flex flex-wrap gap-2">
                    @if (Route::has('temoignages.bulk'))
                        <a href="{{ route('temoignages.bulk') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-table'></i> Saisie en lot / import</a>
                    @endif
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#create-temoignages"><i class='bx bx-plus'></i> Ajouter</button>
                </div>
            @endcan
        </div>
        <div class="card-body">
            <p class="text-muted small">Ne publiez un témoignage qu'avec l'accord de son auteur (cahier §4). Les clients peuvent aussi envoyer le leur, en vidéo ou par écrit, depuis la page <a href="{{ route('site.temoignages.create') }}" target="_blank" rel="noopener">« Partager mon expérience »</a> : il arrive masqué, à vérifier puis publier.</p>
            @if ($moderation)
                <div class="alert alert-warning py-2 d-flex flex-wrap align-items-center gap-2">
                    <span><i class='bx bx-bell me-1'></i>{{ $moderation }} témoignage(s) envoyé(s) par des clients attend(ent) votre validation : regardez-le(s) puis cliquez sur <strong>« Valider »</strong>.</span>
                    <button type="button" class="btn btn-warning btn-sm ms-auto" data-chip-target="a-moderer">Afficher seulement ceux à valider</button>
                </div>
            @endif
            <div class="nv-chips mb-3" id="temoignages-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $temoignages->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="a-moderer">À modérer <b>{{ $moderation }}</b></button>
                <button type="button" class="nv-chip" data-token="avec-video">Vidéos <b>{{ $temoignages->filter(fn ($t) => $t->video_path || $t->video_url)->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-on">Publiés <b>{{ $temoignages->where('is_published', true)->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="statut-off">Masqués <b>{{ $temoignages->where('is_published', false)->count() }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="temoignages-table">
                    <thead>
                        <tr><th width="150">VIDÉO</th><th>AUTEUR</th><th>TÉMOIGNAGE</th><th>STATUT</th><th width="10%">ACTIONS</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($temoignages as $temoignage)
                            <tr>
                                <td>
                                    @if ($temoignage->video_path)
                                        @if ($temoignage->video_status === \App\Models\Temoignage::VIDEO_READY)
                                            <a href="{{ $temoignage->videoUrl() }}" target="_blank" rel="noopener" class="admin-video-thumb" title="Regarder">
                                                @if ($temoignage->posterUrl())<img src="{{ $temoignage->posterUrl() }}" alt="" width="140" height="79">@else<i class='bx bx-video'></i>@endif
                                                <span><i class='bx bx-play'></i>{{ $temoignage->durationForDisplay() }}</span>
                                            </a>
                                        @elseif ($temoignage->video_status === \App\Models\Temoignage::VIDEO_PROCESSING)
                                            <span class="badge bg-info-subtle"><i class='bx bx-loader-alt bx-spin me-1'></i>Préparation…</span>
                                        @else
                                            <span class="badge bg-danger-subtle" title="{{ $temoignage->video_error }}">Pas sur le site</span>
                                        @endif
                                        @unless ($temoignage->video_status === \App\Models\Temoignage::VIDEO_READY)
                                            <a href="{{ $temoignage->fileUrl() }}" target="_blank" rel="noopener" class="d-block small mt-1"><i class='bx bx-play-circle me-1'></i>Ouvrir la vidéo</a>
                                        @endunless
                                    @elseif ($temoignage->video_url)
                                        <a href="{{ $temoignage->video_url }}" target="_blank" rel="noopener" class="small"><i class='bx bx-link-external me-1'></i>{{ $temoignage->videoPlatform() }}</a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="cell-wrap text-break">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($temoignage->photo)<img src="{{ $temoignage->photo->thumbUrl() }}" alt="" width="36" height="36" class="rounded-circle object-fit-cover">@endif
                                        <div>
                                            <div class="fw-semibold">{{ $temoignage->author_name }}</div>
                                            <div class="small text-muted">{{ collect([$temoignage->author_title, $temoignage->organization])->filter()->implode(' — ') }}</div>
                                            @if ($temoignage->rating)<div class="small text-warning">{{ str_repeat('★', $temoignage->rating) }}{{ str_repeat('☆', 5 - $temoignage->rating) }}</div>@endif
                                            {{-- Téléphone : les colonnes Statut et Actions sont hors de l'écran, « Valider » est repris ici. --}}
                                            @if ($temoignage->awaitingModeration())
                                                @can('update', $temoignage)
                                                    <div class="d-lg-none mt-2">@include('admin._publish-button', ['action' => route('temoignages.publication', $temoignage), 'published' => false, 'label' => 'Valider'])</div>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-wrap text-break small">
                                    {{ \Illuminate\Support\Str::limit((string) $temoignage->content, 140) ?: '—' }}
                                    @if ($temoignage->source === 'client')
                                        <div class="text-muted mt-1"><i class='bx bx-globe me-1'></i>Envoyé depuis le site le {{ $temoignage->created_at->format('d/m/Y') }}@if ($temoignage->contact) · {{ $temoignage->contact }}@endif</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="d-none">{{ $temoignage->is_published ? 'statut-on' : 'statut-off' }}{{ $temoignage->awaitingModeration() ? ' a-moderer' : '' }}{{ $temoignage->video_path || $temoignage->video_url ? ' avec-video' : '' }}</span>
                                    @if ($temoignage->awaitingModeration())
                                        <div class="small fw-semibold mb-1" style="color: #b45309;">À modérer</div>
                                        @can('update', $temoignage)
                                            @include('admin._publish-button', ['action' => route('temoignages.publication', $temoignage), 'published' => false, 'label' => 'Valider'])
                                        @else
                                            <span class="badge bg-warning-subtle">En attente</span>
                                        @endcan
                                    @elseif ($temoignage->is_published)
                                        <span class="badge bg-success-subtle">Publié</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Masqué</span>
                                    @endif
                                    @if ($temoignage->is_featured)<span class="badge bg-info-subtle">En avant</span>@endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $temoignage)
                                        @unless ($temoignage->awaitingModeration())
                                            @include('admin._publish-button', ['action' => route('temoignages.publication', $temoignage), 'published' => $temoignage->is_published, 'label' => 'Publier', 'hideLabel' => 'Masquer'])
                                        @endunless
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#edit-temoignages-{{ $temoignage->id }}" title="Modifier"><i class='bx bx-edit'></i></button>
                                    @endcan
                                    @can('delete', $temoignage)
                                        <form method="POST" action="{{ route('temoignages.destroy', $temoignage) }}" class="js-confirm-delete" data-title="Supprimer ce témoignage ?" data-name="{{ $temoignage->author_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-primary btn-sm" title="Supprimer"><i class='bx bx-trash'></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $forms = collect([null])->merge($temoignages->filter(fn ($t) => auth()->user()->can('update', $t)));
    @endphp
    @foreach ($forms as $item)
        @continue($item === null && ! auth()->user()->can('create', \App\Models\Temoignage::class))
        @include('temoignages._form', ['item' => $item])
    @endforeach
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/video-upload.js') }}?v={{ filemtime(public_path('assets/js/video-upload.js')) }}"></script>
    <script>
        const temoignagesTable = $('#temoignages-table').DataTable({ scrollX: false, ordering: false });
        document.querySelectorAll('#temoignages-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#temoignages-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                temoignagesTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });
        document.querySelectorAll('[data-chip-target]').forEach((button) => button.addEventListener('click', () => {
            document.querySelector(`#temoignages-filter .nv-chip[data-token="${button.dataset.chipTarget}"]`).click();
        }));
        @if ($errors->any())
            (function () {
                const id = @json(old('temoignage_id') ? 'edit-temoignages-'.old('temoignage_id') : 'create-temoignages');
                const el = document.getElementById(id);
                if (el) new bootstrap.Modal(el).show();
            })();
        @endif
    </script>
    <style>
        .admin-video-thumb { position: relative; display: inline-block; width: 140px; aspect-ratio: 16 / 9; border-radius: 8px; overflow: hidden; background: #0d2c6b; color: #fff; text-align: center; }
        .admin-video-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .admin-video-thumb > i { font-size: 2rem; line-height: 79px; }
        .admin-video-thumb span { position: absolute; right: 4px; bottom: 4px; background: rgba(0, 0, 0, .65); color: #fff; font-size: .72rem; padding: 0 .35rem; border-radius: 4px; }
        .video-upload-preview { width: 100%; max-height: 220px; border-radius: 10px; background: #000; }
    </style>
    @include('partials.confirm-delete')
@endpush
