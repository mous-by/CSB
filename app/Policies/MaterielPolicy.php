<?php

namespace App\Policies;

use App\Models\Materiel;
use App\Models\User;

/** Permissions du manifeste config/modules/materiels.php. */
class MaterielPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'materiels';
    }

    public function publish(User $user, ?Materiel $materiel = null): bool
    {
        return $user->can('materiels.publish');
    }
}
