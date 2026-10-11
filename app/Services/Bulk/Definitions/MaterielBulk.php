<?php

namespace App\Services\Bulk\Definitions;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Models\Category;
use App\Models\Materiel;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;

/** Produits en lot (catalogue matériel) : les photos s'ajoutent ensuite fiche par fiche. */
class MaterielBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'materiels';
    }

    public function label(): string
    {
        return 'produits';
    }

    public function icon(): string
    {
        return 'bx bx-laptop';
    }

    public function indexRoute(): string
    {
        return 'materiels.index';
    }

    public function columns(): array
    {
        return [
            'designation' => ['label' => 'Désignation', 'required' => true, 'rules' => ['string', 'max:200'], 'unique' => true, 'width' => 40, 'example' => 'Ordinateur portable HP ProBook 450 G9'],
            'reference' => ['label' => 'Référence', 'rules' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._\/-]+$/', 'unique:materiels,reference'], 'unique' => true, 'width' => 16, 'help' => 'Code de l\'article dans le logiciel de stock (facultatif).', 'example' => 'HP-450-G9'],
            'category_id' => ['label' => 'Catégorie', 'required' => true, 'type' => 'select', 'options' => Category::ofType(CategoryType::Materiel)->active()->ordered()->pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(string) $id => $n])->all(), 'width' => 24],
            'brand' => ['label' => 'Marque', 'rules' => ['nullable', 'string', 'max:100'], 'width' => 14],
            'model' => ['label' => 'Modèle', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 18],
            'condition' => ['label' => 'État', 'required' => true, 'type' => 'select', 'options' => collect(MaterielCondition::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(), 'default' => 'neuf', 'width' => 14],
            'price' => ['label' => 'Prix (FCFA)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:999999999'], 'width' => 14, 'help' => 'Vide : « Prix sur demande ».'],
            'promo_price' => ['label' => 'Prix promo (FCFA)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'required_with:rows.*.promo_ends_on', 'lt:rows.*.price'], 'width' => 14, 'help' => 'Facultatif, inférieur au prix : le prix normal s\'affiche barré.'],
            'promo_ends_on' => ['label' => 'Fin de la promo', 'rules' => ['nullable', 'date'], 'width' => 14, 'help' => 'Date au format AAAA-MM-JJ ; vide : sans fin.', 'example' => '2026-12-31'],
            'availability' => ['label' => 'Disponibilité', 'required' => true, 'type' => 'select', 'options' => collect(Availability::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()])->all(), 'default' => 'disponible', 'width' => 16],
            'specs' => ['label' => 'Caractéristiques', 'type' => 'lines', 'rules' => ['array', 'max:30'], 'width' => 50, 'help' => '« Libellé : valeur », séparées par des retours à la ligne ou des « ; ».', 'example' => 'Processeur : Intel Core i5 ; Mémoire : 8 Go'],
            'is_published' => ['label' => 'Publié', 'type' => 'bool', 'default' => false, 'width' => 10],
        ];
    }

    public function extraRules(): array
    {
        return ['specs.*' => ['string', 'max:250', 'regex:/^[^:]+:.+$/']];
    }

    public function create(array $row, User $user): Model
    {
        $row['reference'] = isset($row['reference']) && $row['reference'] !== '' ? mb_strtoupper(trim($row['reference'])) : null;
        $row['specs'] = array_map(fn ($line) => array_map('trim', explode(':', $line, 2)), $row['specs'] ?? []);
        if (! $user->can('materiels.publish')) {
            $row['is_published'] = false;
        }

        return Materiel::create($row + ['sort_order' => 0]);
    }
}
