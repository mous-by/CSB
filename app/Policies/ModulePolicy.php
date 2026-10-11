<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base des policies des modules de contenu : chaque action reflète la permission `module.action` du manifeste.
 * Les règles métier (ex. suppression interdite si l'objet est utilisé) vivent dans le contrôleur ou le service :
 * `Gate::before` contourne la policy pour le superadmin, pas la logique métier.
 */
abstract class ModulePolicy
{
    /** Nom du module (préfixe des permissions). */
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->module().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->module().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->module().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->module().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->module().'.delete');
    }
}
