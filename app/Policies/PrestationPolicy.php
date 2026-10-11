<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/prestations.php. */
class PrestationPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'prestations';
    }
}
