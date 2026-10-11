<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/clients.php. */
class ClientPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'clients';
    }
}
