@extends('layouts.admin')

@section('title', 'Catégories — '.$type->label())

@section('content')
    @php
        $moduleRoute = ['logiciel' => 'logiciels.index', 'materiel' => 'materiels.index', 'article' => 'articles.index'][$type->value];
    @endphp
    @include('admin._crud', [
        'title' => 'Catégories',
        'crumbs' => Route::has($moduleRoute) ? [[$type->label(), route($moduleRoute)]] : [],
        'tabs' => 'categories._tabs',
        'heading' => 'CATÉGORIES — '.mb_strtoupper($type->label()),
        'icon' => 'bx bx-category',
        'intro' => 'Une catégorie utilisée ne peut pas être supprimée : désactivez-la, elle reste sur les contenus qui la portent mais n\'est plus proposée ni affichée en filtre sur le site.',
        'items' => $categories,
        'model' => \App\Models\Category::class,
        'createAbility' => [\App\Models\Category::class, $type],
        'storeUrl' => route('categories.store', $type->value),
        'routePrefix' => 'categories',
        'idField' => 'category_id',
        'statusField' => 'is_active',
        'statusLabels' => ['Active', 'Inactive'],
        'modalSize' => '',
        'deleteTitle' => 'Supprimer cette catégorie ?',
        'newLabel' => 'Nouvelle catégorie',
        'labelOf' => fn ($c) => $c->name,
        'fields' => [
            ['name' => 'name', 'label' => 'Nom', 'required' => true, 'max' => 120],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'max' => 500, 'help' => 'Affichée en introduction du catalogue filtré sur cette catégorie.'],
            ['name' => 'sort_order', 'label' => 'Ordre d\'affichage', 'type' => 'number', 'required' => true, 'default' => 0, 'col' => 'col-md-6'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'switch', 'default' => true, 'col' => 'col-md-6'],
        ],
        'columns' => [
            ['label' => 'CATÉGORIE', 'value' => fn ($c) => '<div class="fw-semibold">'.e($c->name).'</div><div class="small text-muted">'.e($c->slug).'</div>'],
            ['label' => 'CONTENUS', 'value' => fn ($c) => e($usages[$c->id] ?? 0)],
            ['label' => 'ORDRE', 'value' => fn ($c) => e($c->sort_order)],
        ],
    ])
@endsection
