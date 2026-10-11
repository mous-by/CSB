<?php

namespace App\Policies;

use App\Models\Logiciel;
use App\Models\User;

/** Permissions du manifeste config/modules/logiciels.php. */
class LogicielPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'logiciels';
    }

    public function publish(User $user, ?Logiciel $logiciel = null): bool
    {
        return $user->can('logiciels.publish');
    }
}
