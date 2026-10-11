<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/demandes.php. */
class DemandePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'demandes';
    }
}
