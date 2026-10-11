<?php

namespace App\Services\Bulk\Definitions;

use App\Models\Temoignage;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;

/** Témoignages en lot (masqués par défaut : à publier avec l'accord de leur auteur). */
class TemoignageBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'temoignages';
    }

    public function label(): string
    {
        return 'témoignages';
    }

    public function icon(): string
    {
        return 'bx bx-message-square-dots';
    }

    public function indexRoute(): string
    {
        return 'temoignages.index';
    }

    public function columns(): array
    {
        return [
            'author_name' => ['label' => 'Auteur', 'required' => true, 'rules' => ['string', 'max:150'], 'width' => 26],
            'author_title' => ['label' => 'Fonction', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 22],
            'organization' => ['label' => 'Organisation', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 26],
            'content' => ['label' => 'Témoignage', 'required' => true, 'type' => 'textarea', 'rules' => ['string', 'max:1500'], 'width' => 70],
            'is_published' => ['label' => 'Publié', 'type' => 'bool', 'default' => false, 'width' => 10, 'help' => 'Oui seulement si l\'auteur a donné son accord.'],
        ];
    }

    public function create(array $row, User $user): Model
    {
        return Temoignage::create($row + ['sort_order' => 0]);
    }
}
