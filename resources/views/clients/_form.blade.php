{{-- Champs d'un client (création en modale, modification sur la fiche). $client (ou null), $failed. --}}
@php
    $v = fn (string $field) => $failed ? old($field) : $client?->{$field};
    $err = fn (string $field) => $failed && $errors->has($field);
@endphp
<div class="row g-3">
    @foreach ([['name', 'Nom complet', true, 'col-md-6'], ['organization', 'Organisation', false, 'col-md-6'], ['email', 'E-mail', true, 'col-md-6'], ['phone', 'Téléphone', false, 'col-md-6'], ['address', 'Adresse', false, 'col-md-8'], ['city', 'Ville', false, 'col-md-4']] as [$field, $label, $required, $col])
        <div class="{{ $col }}">
            <label for="{{ $field }}-{{ $client?->id ?? 'new' }}" class="form-label fw-semibold">{{ $label }}@if ($required) <span class="text-danger">*</span>@endif</label>
            <input type="{{ $field === 'email' ? 'email' : 'text' }}" class="form-control @if ($err($field)) is-invalid @endif" id="{{ $field }}-{{ $client?->id ?? 'new' }}" name="{{ $field }}" value="{{ $v($field) }}" @required($required)>
            @if ($err($field))<div class="invalid-feedback">{{ $errors->first($field) }}</div>@endif
        </div>
    @endforeach
</div>
