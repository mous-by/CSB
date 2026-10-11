@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite des informations sur votre matériel informatique.')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $category ? $category->name : 'Matériel informatique',
        'crumb' => $category ? $category->name : null,
        'crumbs' => $category ? [['Matériel', route('site.materiels.index')]] : [],
        'intro' => $category?->description ?: 'Ordinateurs, imprimantes, consommables, accessoires et équipements réseau, neufs ou d\'occasion. Prix et disponibilité à confirmer auprès de CSB.',
    ])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.materiels.index') }}" class="filter-bar" role="search" aria-label="Filtrer le matériel" data-ajax-filter="#catalogue-results">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label for="q" class="form-label">Rechercher</label>
                        <input type="search" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Marque, modèle, caractéristique…">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">Toutes</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->slug }}" @selected($category?->id === $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="etat" class="form-label">État</label>
                        <select class="form-select" id="etat" name="etat">
                            <option value="">Tous</option>
                            @foreach (\App\Enums\MaterielCondition::cases() as $item)
                                <option value="{{ $item->value }}" @selected($condition === $item)>{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="disponibilite" class="form-label">Disponibilité</label>
                        <select class="form-select" id="disponibilite" name="disponibilite">
                            <option value="">Toutes</option>
                            @foreach (\App\Enums\Availability::cases() as $item)
                                <option value="{{ $item->value }}" @selected($availability === $item)>{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-1 d-grid">
                        <button type="submit" class="btn btn-primary" aria-label="Filtrer"><i class='bx bx-filter-alt' aria-hidden="true"></i></button>
                    </div>
                </div>
            </form>

            <div id="catalogue-results" data-ajax-results>
                <p class="results-count mb-3" data-results-count>
                    {{ $materiels->total() }} produit{{ $materiels->total() > 1 ? 's' : '' }}{{ $filtered ? ' trouvé'.($materiels->total() > 1 ? 's' : '') : '' }}
                    @if ($filtered)
                        · <a href="{{ route('site.materiels.index') }}" data-ajax-link>Effacer les filtres</a>
                    @endif
                </p>
                @if ($materiels->isEmpty())
                    <div class="empty-state">
                        <i class='bx bx-search-alt' aria-hidden="true"></i>
                        <h2 class="h5 mt-3">{{ $filtered ? 'Aucun produit ne correspond à votre recherche' : 'Notre catalogue de matériel arrive bientôt' }}</h2>
                        <p class="text-muted">Dites-nous ce que vous cherchez : nous vérifions la disponibilité pour vous.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            @if ($filtered)
                                <a href="{{ route('site.materiels.index') }}" class="btn btn-outline-primary" data-ajax-link>Voir tout le matériel</a>
                            @endif
                            @if (Route::has('site.devis'))
                                <a href="{{ route('site.devis', ['type' => 'materiel']) }}" class="btn btn-accent">Demander un devis</a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($materiels as $materiel)
                            <div class="col-sm-6 col-lg-4 col-xl-3">
                                @include('site.materiels._card', ['materiel' => $materiel])
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 d-flex justify-content-center">{{ $materiels->onEachSide(1)->links() }}</div>
                @endif
            </div>
            <p class="small text-muted mt-4 mb-0"><i class='bx bx-info-circle' aria-hidden="true"></i> Catalogue indicatif : les prix et la disponibilité sont mis à jour par CSB et restent à confirmer au moment de la demande.</p>
        </div>
    </section>

    @include('site.partials.cta', ['title' => 'Besoin d\'équiper vos bureaux ?', 'text' => 'Ordinateurs, imprimantes, réseau : CSB vous conseille et vous fait une proposition adaptée à votre budget.'])
@endsection
