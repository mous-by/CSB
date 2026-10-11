{{-- Rappel du contenu concerné par la demande (fiche préremplie, cahier §6.4). $image, $label, $hint, $url. --}}
<div class="subject-reminder">
    @if ($image)
        <img src="{{ $image->thumbUrl() }}" alt="">
    @else
        <span class="icon-box mb-0" style="width:56px;height:56px"><i class='bx {{ $icon ?? 'bx-package' }}' aria-hidden="true"></i></span>
    @endif
    <div>
        <span class="small text-muted d-block">{{ $hint }}</span>
        <a href="{{ $url }}" class="fw-bold text-decoration-none">{{ $label }}</a>
    </div>
</div>
