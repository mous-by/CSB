@extends('layouts.admin')

@section('title', 'Services')

@section('content')
    @include('admin._crud', [
        'title' => 'Services',
        'heading' => 'SERVICES DE CSB',
        'icon' => 'bx bx-wrench',
        'intro' => 'Les services publiés apparaissent sur la page Services et sur l\'accueil, dans l\'ordre d\'affichage (du plus petit au plus grand).',
        'items' => $prestations,
        'model' => \App\Models\Prestation::class,
        'routePrefix' => 'prestations',
        'idField' => 'prestation_id',
        'deleteTitle' => 'Supprimer ce service ?',
        'newLabel' => 'Nouveau service',
        'labelOf' => fn ($p) => $p->title,
        'fields' => [
            ['name' => 'title', 'label' => 'Titre', 'required' => true, 'max' => 150, 'placeholder' => 'Réparation et maintenance', 'col' => 'col-md-8'],
            ['name' => 'icon', 'label' => 'Icône', 'type' => 'icon', 'required' => true, 'options' => \App\Models\Prestation::ICONS, 'default' => 'bx-check-circle', 'col' => 'col-md-4'],
            ['name' => 'summary', 'label' => 'Résumé', 'type' => 'textarea', 'required' => true, 'max' => 300, 'help' => 'Deux lignes au plus : affiché sur la carte du service.'],
            ['name' => 'description', 'label' => 'Description détaillée', 'type' => 'markdown', 'max' => 10000],
            ['name' => 'sort_order', 'label' => 'Ordre d\'affichage', 'type' => 'number', 'required' => true, 'default' => 0, 'col' => 'col-md-4'],
            ['name' => 'is_published', 'label' => 'Publié sur le site', 'type' => 'switch', 'default' => true, 'col' => 'col-md-8'],
        ],
        'columns' => [
            ['label' => 'SERVICE', 'value' => fn ($p) => '<div class="d-flex gap-2"><i class="bx '.e($p->icon).' fs-4 text-primary"></i><div><div class="fw-semibold">'.e($p->title).'</div><div class="small text-muted">'.e(\Illuminate\Support\Str::limit($p->summary, 110)).'</div></div></div>'],
            ['label' => 'ORDRE', 'value' => fn ($p) => e($p->sort_order)],
        ],
    ])
@endsection
