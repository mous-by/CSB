<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/temoignages.php. */
class TemoignagePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'temoignages';
    }
}
