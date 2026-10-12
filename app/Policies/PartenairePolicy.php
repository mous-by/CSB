<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Permissions du manifeste config/modules/partenaires.php (« manage » pour créer et modifier). */
class PartenairePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'partenaires';
    }

    public function create(User $user): bool
    {
        return $user->can('partenaires.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('partenaires.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('partenaires.manage');
    }
}
