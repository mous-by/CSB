{{-- Champ d'un formulaire public : label relié, erreur reliée (aria-describedby), aide facultative.
     $name, $label, $type (text|email|tel|textarea|select), $required, $max, $placeholder, $options, $value, $help, $rows, $autocomplete. --}}
@php
    $type = $type ?? 'text';
    $id = 'f-'.$name;
    $value = old($name, $value ?? null);
    $describedBy = trim(($errors->has($name) ? $id.'-error ' : '').(! empty($help) ? $id.'-help' : ''));
@endphp
<label for="{{ $id }}" class="form-label {{ ($required ?? false) ? 'required' : '' }}">{{ $label }}</label>
@if ($type === 'textarea')
    <textarea class="form-control @error($name) is-invalid @enderror" id="{{ $id }}" name="{{ $name }}" rows="{{ $rows ?? 5 }}" maxlength="{{ $max ?? 5000 }}" placeholder="{{ $placeholder ?? '' }}" @required($required ?? false) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>{{ $value }}</textarea>
@elseif ($type === 'select')
    <select class="form-select @error($name) is-invalid @enderror" id="{{ $id }}" name="{{ $name }}" @required($required ?? false) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        <option value="">— Choisir —</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
@else
    <input type="{{ $type }}" class="form-control @error($name) is-invalid @enderror" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" maxlength="{{ $max ?? 190 }}" placeholder="{{ $placeholder ?? '' }}"
           @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif @required($required ?? false) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>
@endif
@error($name)<div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $message }}</div>@enderror
@if (! empty($help))<div class="form-text" id="{{ $id }}-help">{{ $help }}</div>@endif
