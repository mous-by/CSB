@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Tableau de bord</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><i class="bx bx-home-alt"></i></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $dashboard['heading'] }}</li>
                </ol>
            </nav>
        </div>
        {{-- Site public dans un nouvel onglet, puis les deux premiers raccourcis de gestion (cahier §7), selon les permissions. --}}
        <div class="ms-auto d-flex flex-wrap gap-2">
            <a href="{{ route('site.home') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class='bx bx-globe me-1'></i>Voir le site</a>
            @foreach (array_slice($dashboard['quick'], 0, 2) as $link)
                <a href="{{ route($link['route'], $link['params']) }}" class="btn btn-primary btn-sm"><i class='{{ $link['icon'] }} me-1'></i>{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>
    <hr />

    @include('dashboard._body')
@endsection
