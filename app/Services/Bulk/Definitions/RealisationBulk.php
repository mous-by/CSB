<?php

namespace App\Services\Bulk\Definitions;

use App\Models\Realisation;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;

/** Réalisations en lot : visuels ajoutés ensuite fiche par fiche, publication avec l'accord du client. */
class RealisationBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'realisations';
    }

    public function label(): string
    {
        return 'réalisations';
    }

    public function icon(): string
    {
        return 'bx bx-briefcase-alt-2';
    }

    public function indexRoute(): string
    {
        return 'realisations.index';
    }

    public function columns(): array
    {
        return [
            'title' => ['label' => 'Titre', 'required' => true, 'rules' => ['string', 'max:200'], 'unique' => true, 'width' => 30],
            'client_name' => ['label' => 'Client', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 24],
            'sector' => ['label' => 'Secteur', 'rules' => ['nullable', 'string', 'max:100'], 'width' => 18],
            'year' => ['label' => 'Année', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 1)], 'width' => 10],
            'summary' => ['label' => 'Résumé', 'required' => true, 'type' => 'textarea', 'rules' => ['string', 'max:300'], 'width' => 60],
            'technologies' => ['label' => 'Technologies', 'type' => 'lines', 'rules' => ['array', 'max:20'], 'width' => 30, 'example' => 'Laravel ; MySQL'],
            'is_published' => ['label' => 'Publié', 'type' => 'bool', 'default' => false, 'width' => 10],
        ];
    }

    public function extraRules(): array
    {
        return ['technologies.*' => ['string', 'max:60']];
    }

    public function create(array $row, User $user): Model
    {
        if (! $user->can('realisations.publish')) {
            $row['is_published'] = false;
        }

        return Realisation::create($row + ['sort_order' => 0]);
    }
}
