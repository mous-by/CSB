<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/abonnes.php. */
class AbonnePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'abonnes';
    }
}
