@extends('layouts.admin')

@section('title', 'Kit — '.$data['title'])

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Réseaux sociaux</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('kit.index') }}">Kit de publication</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $data['title'] }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @unless ($partenaire)
        <div class="alert alert-info small py-2">Vous n'êtes pas un partenaire : les textes contiennent le lien normal du site (sans attribution de commission).</div>
    @endunless

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-image me-2'></i>VISUELS PRÊTS À POSTER</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach (['carre' => 'Carré — Facebook, Instagram', 'story' => 'Story — statut WhatsApp, Instagram, TikTok'] as $format => $label)
                            <div class="col-{{ $format === 'carre' ? '7' : '5' }}">
                                <div class="small fw-semibold mb-1">{{ $label }}</div>
                                <a href="{{ route('kit.image', [$type, $model->id, $format]) }}" target="_blank" rel="noopener"><img src="{{ route('kit.image', [$type, $model->id, $format]) }}" alt="Visuel {{ $label }}" class="img-fluid rounded border" loading="lazy"></a>
                                <a href="{{ route('kit.image', [$type, $model->id, $format, 'download' => 1]) }}" class="btn btn-primary btn-sm w-100 mt-2"><i class='bx bx-download me-1'></i>Télécharger</a>
                            </div>
                        @endforeach
                    </div>
                    @if ($data['image'])
                        <a href="{{ $data['image']->url() }}" download class="btn btn-outline-primary btn-sm w-100 mt-3"><i class='bx bx-photo-album me-1'></i>Télécharger la photo d'origine</a>
                    @endif
                    <div class="mt-3">
                        <label class="form-label small fw-semibold" for="kit-link">Lien à partager</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="kit-link" value="{{ $data['url'] }}" readonly>
                            <button class="btn btn-outline-primary" type="button" data-copy="#kit-link"><i class='bx bx-copy'></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-text me-2'></i>TEXTES PRÊTS À COPIER</h6></div>
                <div class="card-body">
                    @foreach ($texts as $network => $text)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0" for="text-{{ $network }}"><i class='bx {{ $text['icon'] }} me-1'></i>{{ $text['label'] }}</label>
                                <button class="btn btn-outline-primary btn-sm" type="button" data-copy="#text-{{ $network }}"><i class='bx bx-copy'></i> Copier</button>
                            </div>
                            <textarea class="form-control small" id="text-{{ $network }}" rows="{{ min(12, substr_count($text['text'], "\n") + 2) }}">{{ $text['text'] }}</textarea>
                        </div>
                    @endforeach
                    <p class="small text-muted mb-0">Vous pouvez retoucher le texte avant de le copier. Pensez à mentionner le partenariat (« en partenariat avec CSB »).</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => {
            const field = document.querySelector(b.dataset.copy);
            field.select();
            const done = () => { const old = b.innerHTML; b.innerHTML = "<i class='bx bx-check'></i> Copié"; setTimeout(() => { b.innerHTML = old; }, 1500); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(field.value).then(done); } else { document.execCommand('copy'); done(); }
        }));
    </script>
@endpush
