<?php

namespace App\Policies;

use App\Models\Realisation;
use App\Models\User;

/** Permissions du manifeste config/modules/realisations.php. */
class RealisationPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'realisations';
    }

    public function publish(User $user, ?Realisation $realisation = null): bool
    {
        return $user->can('realisations.publish');
    }
}
