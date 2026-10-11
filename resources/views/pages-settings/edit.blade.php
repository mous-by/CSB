@extends('layouts.admin')

@section('title', 'Pages et paramètres')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pages et paramètres</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @php
        $group = $groups[$active];
        $failed = $errors->any() && old('_group') === $active;
        $previews = ['accueil' => 'site.home', 'a-propos' => 'site.about', 'legal' => 'site.legal'];
        $name = fn (string $key) => \App\Http\Requests\Web\PageSettingsRequest::inputName($key);
    @endphp

    <nav class="nv-tabs mb-3" aria-label="Onglets des paramètres">
        @foreach ($groups as $key => $tab)
            <a href="{{ route('pages.edit', ['onglet' => $key]) }}" class="nv-tab {{ $key === $active ? 'active' : '' }}">
                <i class='{{ $tab['icon'] }}'></i>{{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('pages.update', $active) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="_group" value="{{ $active }}">

        <div class="card">
            <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 text-white"><i class='{{ $group['icon'] }} me-2'></i>{{ mb_strtoupper($group['label']) }}</h6>
                @if (isset($previews[$active]))
                    <a href="{{ route($previews[$active]) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-show'></i> Voir la page</a>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($group['fields'] as $key => $field)
                        @php
                            [$label, $type, $max] = $field;
                            $help = $field[3] ?? null;
                            $input = $name($key);
                            $value = $failed ? old($input) : $settings->get($key);
                            $wide = in_array($type, ['textarea', 'markdown'], true);
                        @endphp
                        @if ($type === 'image')
                            <div class="col-md-6">
                                @include('admin._media-picker', ['name' => $input, 'id' => $input, 'label' => $label, 'selected' => [$value ? \App\Models\Media::find($value) : null], 'upload' => $input.'_upload', 'help' => $help])
                            </div>
                            @continue
                        @endif
                        <div class="{{ $wide ? 'col-12' : 'col-md-6' }}">
                            <label for="{{ $input }}" class="form-label fw-semibold">{{ $label }}</label>
                            @if ($wide)
                                <textarea class="form-control @if ($type === 'markdown') font-monospace small @endif @error($input) is-invalid @enderror" id="{{ $input }}" name="{{ $input }}"
                                          rows="{{ $type === 'markdown' ? 12 : 3 }}" maxlength="{{ $max }}" aria-describedby="{{ $input }}-help">{{ $value }}</textarea>
                            @else
                                <input type="{{ in_array($type, ['tel', 'tels'], true) ? 'tel' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text')) }}" class="form-control @error($input) is-invalid @enderror"
                                       id="{{ $input }}" name="{{ $input }}" value="{{ $value }}" maxlength="{{ $max }}" aria-describedby="{{ $input }}-help">
                            @endif
                            @error($input)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="{{ $input }}-help">
                                {{ $help }}
                                @if ($type === 'markdown')
                                    Mise en forme Markdown : <code>## Titre</code>, <code>**gras**</code>, <code>- liste</code>, <code>[texte du lien](adresse)</code>.
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end">
                <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
            </div>
        </div>
    </form>
@endsection
