<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/boutique.php. */
class CommandePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'boutique';
    }
}
