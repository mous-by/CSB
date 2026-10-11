<?php

namespace App\Services\Bulk\Definitions;

use App\Models\Prestation;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;

/** Services de CSB en lot (page Services). */
class PrestationBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'prestations';
    }

    public function label(): string
    {
        return 'services';
    }

    public function icon(): string
    {
        return 'bx bx-wrench';
    }

    public function indexRoute(): string
    {
        return 'prestations.index';
    }

    public function columns(): array
    {
        return [
            'title' => ['label' => 'Titre', 'required' => true, 'rules' => ['string', 'max:150'], 'unique' => true, 'width' => 34, 'example' => 'Réparation d\'imprimantes'],
            'icon' => ['label' => 'Icône', 'type' => 'select', 'options' => Prestation::ICONS, 'default' => 'bx-check-circle', 'width' => 20],
            'summary' => ['label' => 'Résumé', 'required' => true, 'type' => 'textarea', 'rules' => ['string', 'max:300'], 'width' => 60],
            'sort_order' => ['label' => 'Ordre', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999'], 'default' => 0, 'width' => 10],
            'is_published' => ['label' => 'Publié', 'type' => 'bool', 'default' => true, 'width' => 10],
        ];
    }

    public function create(array $row, User $user): Model
    {
        return Prestation::create(['icon' => $row['icon'] ?? 'bx-check-circle', 'sort_order' => $row['sort_order'] ?? 0] + $row);
    }
}
