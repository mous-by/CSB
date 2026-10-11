<?php

namespace App\Services\Bulk\Definitions;

use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Database\Eloquent\Model;

/** Abonnés en lot (liste existante de clients ayant accepté de recevoir les actualités). */
class AbonneBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'abonnes';
    }

    public function label(): string
    {
        return 'abonnés';
    }

    public function icon(): string
    {
        return 'bx bx-mail-send';
    }

    public function indexRoute(): string
    {
        return 'abonnes.index';
    }

    public function maxRows(): int
    {
        return 2000;
    }

    public function columns(): array
    {
        return [
            'email' => ['label' => 'E-mail', 'required' => true, 'rules' => ['email', 'max:190'], 'unique' => true, 'width' => 34],
            'name' => ['label' => 'Nom', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 28],
        ];
    }

    public function instructions(): array
    {
        return ['N\'ajoutez que des personnes qui ont accepté de recevoir les actualités de CSB. Une adresse déjà inscrite est simplement conservée.'];
    }

    public function create(array $row, User $user): Model
    {
        return app(NewsletterService::class)->subscribe($row['email'], $row['name'] ?? null, 'administration');
    }
}
