@extends('layouts.admin')

@section('title', 'Témoignages')

@section('content')
    @include('admin._crud', [
        'title' => 'Témoignages',
        'heading' => 'TÉMOIGNAGES DE CLIENTS',
        'icon' => 'bx bx-message-square-dots',
        'intro' => 'Ne publiez un témoignage qu\'avec l\'accord écrit de son auteur (cahier §4 « témoignages autorisés »). Un nouveau témoignage est masqué par défaut.',
        'items' => $temoignages,
        'model' => \App\Models\Temoignage::class,
        'routePrefix' => 'temoignages',
        'idField' => 'temoignage_id',
        'deleteTitle' => 'Supprimer ce témoignage ?',
        'newLabel' => 'Nouveau témoignage',
        'labelOf' => fn ($t) => $t->author_name,
        'fields' => [
            ['name' => 'author_name', 'label' => 'Nom de l\'auteur', 'required' => true, 'max' => 150, 'col' => 'col-md-6'],
            ['name' => 'author_title', 'label' => 'Fonction', 'max' => 150, 'placeholder' => 'Directeur', 'col' => 'col-md-6'],
            ['name' => 'organization', 'label' => 'Organisation', 'max' => 150, 'col' => 'col-md-6'],
            ['name' => 'sort_order', 'label' => 'Ordre d\'affichage', 'type' => 'number', 'required' => true, 'default' => 0, 'col' => 'col-md-6'],
            ['name' => 'content', 'label' => 'Témoignage', 'type' => 'textarea', 'required' => true, 'max' => 1500],
            ['name' => 'is_published', 'label' => 'Publié (accord de l\'auteur obtenu)', 'type' => 'switch', 'default' => false],
        ],
        'columns' => [
            ['label' => 'AUTEUR', 'value' => fn ($t) => '<div class="fw-semibold">'.e($t->author_name).'</div><div class="small text-muted">'.e(collect([$t->author_title, $t->organization])->filter()->implode(' — ')).'</div>'],
            ['label' => 'TÉMOIGNAGE', 'value' => fn ($t) => e(\Illuminate\Support\Str::limit($t->content, 140))],
        ],
    ])
@endsection
